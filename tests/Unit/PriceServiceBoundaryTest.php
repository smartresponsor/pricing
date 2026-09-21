<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Unit;

use App\Pricing\DTO\PriceDefinitionDTO;
use App\Pricing\DTO\PriceListDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSelectionResultDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\Entity\PriceHistoryEntity;
use App\Pricing\Exception\PriceHistoryConflictException;
use App\Pricing\RepositoryInterface\PriceHistoryRepositoryInterface;
use App\Pricing\Service\PriceHistorySerializationService;
use App\Pricing\Service\PriceHistoryService;
use App\Pricing\Service\PriceSelectionService;
use App\Pricing\Service\PriceSelectionSnapshotService;
use PHPUnit\Framework\TestCase;

/** Exercises race, provenance, persistence, and ranking safety paths. */
final class PriceServiceBoundaryTest extends TestCase
{
    /** Proves history entities expose all persisted provenance and reject each invalid identity coordinate. */
    public function testHistoryEntityAccessorsAndInvariantBranches(): void
    {
        $recordedAt = new \DateTimeImmutable('2026-09-21T12:00:00+00:00');
        $entity = new PriceHistoryEntity(
            'set',
            2,
            'catalog:variant:1',
            ['id' => 'set'],
            str_repeat('a', 64),
            $recordedAt,
        );

        self::assertNull($entity->id());
        self::assertSame('set', $entity->priceSetId());
        self::assertSame(2, $entity->revision());
        self::assertSame('catalog:variant:1', $entity->priceableReference());
        self::assertSame(['id' => 'set'], $entity->payload());
        self::assertSame(str_repeat('a', 64), $entity->payloadHash());
        self::assertSame($recordedAt, $entity->recordedAt());

        foreach ([
            static fn () => new PriceHistoryEntity('', 1, 'ref', [], str_repeat('a', 64), new \DateTimeImmutable()),
            static fn () => new PriceHistoryEntity('set', 1, '', [], str_repeat('a', 64), new \DateTimeImmutable()),
            static fn () => new PriceHistoryEntity('set', 0, 'ref', [], str_repeat('a', 64), new \DateTimeImmutable()),
            static fn () => new PriceHistoryEntity('set', 1, 'ref', [], 'invalid', new \DateTimeImmutable()),
        ] as $factory) {
            try {
                $factory();
                self::fail('Expected invalid price history entity state to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** Proves append-only recording accepts an identical concurrent winner after a uniqueness race. */
    public function testHistoryRecordReturnsIdenticalConcurrentWinner(): void
    {
        $repository = new PriceConcurrentHistoryRepository(true);
        $service = new PriceHistoryService($repository, new PriceHistorySerializationService());

        $history = $service->record($this->priceSet());

        self::assertSame($repository->concurrent, $history);
    }

    /** Proves append-only recording rethrows a uniqueness race when no identical winner exists. */
    public function testHistoryRecordRethrowsUnresolvedConcurrentConflict(): void
    {
        $service = new PriceHistoryService(
            new PriceConcurrentHistoryRepository(false),
            new PriceHistorySerializationService(),
        );

        $this->expectException(PriceHistoryConflictException::class);
        $service->record($this->priceSet());
    }

    /** Proves a concurrent winner with different content cannot satisfy an idempotent append. */
    public function testHistoryRecordRejectsDifferentConcurrentWinner(): void
    {
        $repository = new class implements PriceHistoryRepositoryInterface {
            private ?PriceHistoryEntity $concurrent = null;

            public function findRevision(string $priceSetId, int $revision): ?PriceHistoryEntity
            {
                return $this->concurrent;
            }

            public function save(PriceHistoryEntity $history): void
            {
                $this->concurrent = new PriceHistoryEntity(
                    $history->priceSetId(),
                    $history->revision(),
                    $history->priceableReference(),
                    ['different' => true],
                    str_repeat('0', 64),
                    $history->recordedAt(),
                );

                throw new PriceHistoryConflictException('Simulated different concurrent revision insert.');
            }
        };
        $service = new PriceHistoryService($repository, new PriceHistorySerializationService());

        $this->expectException(PriceHistoryConflictException::class);
        $service->record($this->priceSet());
    }

    /** Proves historical lookup validates id and revision independently before repository access. */
    public function testHistoryLoadRejectsInvalidCoordinates(): void
    {
        $service = new PriceHistoryService(
            new PriceConcurrentHistoryRepository(false),
            new PriceHistorySerializationService(),
        );

        foreach ([['', 1], ['set', 0]] as [$id, $revision]) {
            try {
                $service->load($id, $revision);
                self::fail('Expected invalid history coordinates to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** Proves snapshot creation rejects each independent set/result provenance mismatch. */
    public function testSnapshotRejectsEveryResultIdentityMismatch(): void
    {
        $set = $this->priceSet();
        $criteria = new PriceSelectionCriteriaDTO('USD', 1, new \DateTimeImmutable('2026-09-21T12:00:00+00:00'));
        $selected = $set->prices[0];
        $service = new PriceSelectionSnapshotService();

        $results = [
            new PriceSelectionResultDTO($selected, [$selected->id], [], 'mismatch', $set->revision + 1),
            new PriceSelectionResultDTO(
                new PriceDefinitionDTO('price', 'other-set', $set->priceableReference, 'USD', 100),
                ['price'],
                [],
                'mismatch',
                $set->revision,
            ),
            new PriceSelectionResultDTO(
                new PriceDefinitionDTO('price', $set->id, 'other-reference', 'USD', 100),
                ['price'],
                [],
                'mismatch',
                $set->revision,
            ),
        ];

        foreach ($results as $result) {
            try {
                $service->snapshot($set, $criteria, $result);
                self::fail('Expected mismatched selection provenance to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** Proves comparison handles two concrete price lists instead of relying on a base-price null list. */
    public function testSelectionRanksTwoConcretePriceLists(): void
    {
        $set = new PriceSetDTO(
            'set-ranked',
            'catalog:variant:ranked',
            [
                new PriceDefinitionDTO('low-priority', 'set-ranked', 'catalog:variant:ranked', 'USD', 50, 'low'),
                new PriceDefinitionDTO('high-priority', 'set-ranked', 'catalog:variant:ranked', 'USD', 100, 'high'),
            ],
            [
                new PriceListDTO('low', priority: 10),
                new PriceListDTO('high', priority: 20),
            ],
        );

        $result = (new PriceSelectionService())->select(
            $set,
            new PriceSelectionCriteriaDTO('USD', 1, new \DateTimeImmutable('2026-09-21T12:00:00+00:00')),
        );

        self::assertSame('high-priority', $result->selected->id);
    }

    private function priceSet(): PriceSetDTO
    {
        return new PriceSetDTO(
            'set',
            'catalog:variant:1',
            [new PriceDefinitionDTO('price', 'set', 'catalog:variant:1', 'USD', 100)],
            revision: 2,
        );
    }
}

/** Repository double that deterministically simulates a uniqueness race during append-only insertion. */
final class PriceConcurrentHistoryRepository implements PriceHistoryRepositoryInterface
{
    public ?PriceHistoryEntity $concurrent = null;

    public function __construct(private readonly bool $publishWinner)
    {
    }

    public function findRevision(string $priceSetId, int $revision): ?PriceHistoryEntity
    {
        return $this->concurrent;
    }

    public function save(PriceHistoryEntity $history): void
    {
        if ($this->publishWinner) {
            $this->concurrent = $history;
        }

        throw new PriceHistoryConflictException('Simulated concurrent revision insert.');
    }
}
