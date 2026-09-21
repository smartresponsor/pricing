<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Unit;

use App\Pricing\DTO\PriceDefinitionDTO;
use App\Pricing\DTO\PriceListDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\Entity\PriceHistoryEntity;
use App\Pricing\Exception\PriceHistoryConflictException;
use App\Pricing\Exception\PriceHistoryNotFoundException;
use App\Pricing\RepositoryInterface\PriceHistoryRepositoryInterface;
use App\Pricing\Service\PriceHistoryCodec;
use App\Pricing\Service\PriceHistoryService;
use PHPUnit\Framework\TestCase;

/** Verifies stable history serialization and append-only revision semantics. */
final class PriceHistoryServiceTest extends TestCase
{
    /** Proves full PriceSet semantics survive an encode/decode history round trip. */
    public function testCodecRoundTripPreservesRevisionSemantics(): void
    {
        $set = $this->priceSet();
        $codec = new PriceHistoryCodec();
        $payload = $codec->encode($set);
        $restored = $codec->decode($payload);

        self::assertSame($set->id, $restored->id);
        self::assertSame($set->revision, $restored->revision);
        self::assertSame($set->priceableReference, $restored->priceableReference);
        self::assertSame('vip', $restored->priceLists[0]->id);
        self::assertSame(3, $restored->priceLists[0]->revision);
        self::assertSame(1200, $restored->prices[0]->amountMinor);
        self::assertSame(1500, $restored->prices[0]->referenceAmountMinor);
        self::assertTrue($restored->prices[0]->taxIncluded);
        self::assertSame($codec->hash($payload), $codec->hash($codec->encode($restored)));
    }

    /** Proves recording the same immutable revision twice is idempotent. */
    public function testRecordIsIdempotentForIdenticalRevision(): void
    {
        $repository = new PriceHistoryMemoryRepository();
        $service = new PriceHistoryService($repository, new PriceHistoryCodec());
        $set = $this->priceSet();

        $first = $service->record($set, new \DateTimeImmutable('2026-09-20T20:00:00+00:00'));
        $second = $service->record($set, new \DateTimeImmutable('2026-09-20T21:00:00+00:00'));

        self::assertSame($first, $second);
        self::assertCount(1, $repository->records);
        self::assertSame(7, $service->load('set-history', 7)->revision);
    }

    /** Proves revision reuse with different pricing content is rejected. */
    public function testRecordRejectsConflictingRevisionReuse(): void
    {
        $repository = new PriceHistoryMemoryRepository();
        $service = new PriceHistoryService($repository, new PriceHistoryCodec());
        $service->record($this->priceSet());

        $changed = new PriceSetDTO(
            'set-history',
            'catalog:variant:900',
            [new PriceDefinitionDTO('vip-price', 'set-history', 'catalog:variant:900', 'USD', 1100)],
            revision: 7,
        );

        $this->expectException(PriceHistoryConflictException::class);
        $service->record($changed);
    }

    /** Proves missing historical revisions fail explicitly instead of returning current pricing state. */
    public function testLoadRejectsMissingRevision(): void
    {
        $service = new PriceHistoryService(new PriceHistoryMemoryRepository(), new PriceHistoryCodec());

        $this->expectException(PriceHistoryNotFoundException::class);
        $service->load('missing-set', 1);
    }

    /** Proves persisted payload tampering is detected before historical reconstruction. */
    public function testLoadRejectsPayloadIntegrityMismatch(): void
    {
        $repository = new PriceHistoryMemoryRepository();
        $codec = new PriceHistoryCodec();
        $payload = $codec->encode($this->priceSet());
        $repository->records['set-history:7'] = new PriceHistoryEntity(
            'set-history',
            7,
            'catalog:variant:900',
            $payload,
            str_repeat('0', 64),
            new \DateTimeImmutable('2026-09-21T13:40:00+00:00'),
        );
        $service = new PriceHistoryService($repository, $codec);

        $this->expectException(PriceHistoryConflictException::class);
        $service->load('set-history', 7);
    }

    private function priceSet(): PriceSetDTO
    {
        return new PriceSetDTO(
            'set-history',
            'catalog:variant:900',
            [
                new PriceDefinitionDTO(
                    'vip-price',
                    'set-history',
                    'catalog:variant:900',
                    'USD',
                    1200,
                    'vip',
                    minimumQuantity: 2,
                    maximumQuantity: 10,
                    context: ['region' => 'us'],
                    startsAt: new \DateTimeImmutable('2026-09-01T00:00:00+00:00'),
                    endsAt: new \DateTimeImmutable('2026-10-01T00:00:00+00:00'),
                    referenceAmountMinor: 1500,
                    taxIncluded: true,
                    revision: 4,
                ),
            ],
            [
                new PriceListDTO(
                    'vip',
                    priority: 20,
                    context: ['customerGroup' => 'vip'],
                    startsAt: new \DateTimeImmutable('2026-09-01T00:00:00+00:00'),
                    endsAt: new \DateTimeImmutable('2026-10-01T00:00:00+00:00'),
                    revision: 3,
                ),
            ],
            7,
        );
    }
}

/** In-memory repository used only to exercise PriceHistoryService behavior. */
final class PriceHistoryMemoryRepository implements PriceHistoryRepositoryInterface
{
    /** @var array<string, PriceHistoryEntity> */
    public array $records = [];

    public function findRevision(string $priceSetId, int $revision): ?PriceHistoryEntity
    {
        return $this->records[$priceSetId.':'.$revision] ?? null;
    }

    public function save(PriceHistoryEntity $history): void
    {
        $key = $history->priceSetId().':'.$history->revision();
        if (isset($this->records[$key])) {
            throw new PriceHistoryConflictException('Duplicate revision.');
        }

        $this->records[$key] = $history;
    }
}
