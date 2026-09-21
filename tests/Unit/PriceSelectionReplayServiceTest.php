<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Unit;

use App\Pricing\DTO\PriceDefinitionDTO;
use App\Pricing\DTO\PriceListDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\Exception\PriceReplayMismatchException;
use App\Pricing\Service\PriceSelectionReplayService;
use App\Pricing\Service\PriceSelectionService;
use App\Pricing\Service\PriceSelectionSnapshotService;
use PHPUnit\Framework\TestCase;

/** Verifies auditable snapshot creation and deterministic historical replay. */
final class PriceSelectionReplayServiceTest extends TestCase
{
    /** Proves a selected price can be snapshotted and reproduced from its historical set revision. */
    public function testSnapshotReplaysAgainstHistoricalRevision(): void
    {
        $list = new PriceListDTO('vip', priority: 20, context: ['group' => 'vip'], revision: 3);
        $set = new PriceSetDTO(
            'set-history',
            'catalog:variant:701',
            [
                new PriceDefinitionDTO('base', 'set-history', 'catalog:variant:701', 'USD', 1500),
                new PriceDefinitionDTO(
                    'vip-price',
                    'set-history',
                    'catalog:variant:701',
                    'USD',
                    1200,
                    'vip',
                    referenceAmountMinor: 1500,
                    taxIncluded: true,
                    revision: 4,
                ),
            ],
            [$list],
            9,
        );
        $criteria = new PriceSelectionCriteriaDTO(
            'USD',
            2,
            new \DateTimeImmutable('2026-09-20T18:00:00+00:00'),
            ['group' => 'vip'],
        );
        $selector = new PriceSelectionService();
        $result = $selector->select($set, $criteria);
        $snapshot = (new PriceSelectionSnapshotService())->snapshot($set, $criteria, $result);

        self::assertSame('vip-price', $snapshot->priceId);
        self::assertSame(9, $snapshot->priceSetRevision);
        self::assertSame(3, $snapshot->priceListRevision);
        self::assertSame(4, $snapshot->priceRevision);
        self::assertSame(1200, $snapshot->amountMinor);
        self::assertSame(1500, $snapshot->referenceAmountMinor);
        self::assertTrue($snapshot->taxIncluded);

        $replayed = (new PriceSelectionReplayService($selector))->replay($set, $snapshot);
        self::assertSame('vip-price', $replayed->selected->id);
    }

    /** Proves replay rejects a different PriceSet revision before selecting from it. */
    public function testReplayRejectsDifferentSetRevision(): void
    {
        $set = new PriceSetDTO(
            'set-history',
            'catalog:variant:702',
            [new PriceDefinitionDTO('price', 'set-history', 'catalog:variant:702', 'USD', 500)],
            revision: 2,
        );
        $criteria = new PriceSelectionCriteriaDTO('USD', 1, new \DateTimeImmutable('2026-09-20T18:00:00+00:00'));
        $selector = new PriceSelectionService();
        $snapshot = (new PriceSelectionSnapshotService())->snapshot($set, $criteria, $selector->select($set, $criteria));
        $differentRevision = new PriceSetDTO(
            'set-history',
            'catalog:variant:702',
            [new PriceDefinitionDTO('price', 'set-history', 'catalog:variant:702', 'USD', 500)],
            revision: 3,
        );

        $this->expectException(PriceReplayMismatchException::class);
        (new PriceSelectionReplayService($selector))->replay($differentRevision, $snapshot);
    }

    /** Proves replay detects silent price mutation even when a caller reuses revision numbers incorrectly. */
    public function testReplayRejectsMutatedPriceWithinSameRevision(): void
    {
        $original = new PriceSetDTO('set-history', 'catalog:variant:703', [
            new PriceDefinitionDTO('price', 'set-history', 'catalog:variant:703', 'USD', 500, revision: 2),
        ], revision: 4);
        $criteria = new PriceSelectionCriteriaDTO('USD', 1, new \DateTimeImmutable('2026-09-20T18:00:00+00:00'));
        $selector = new PriceSelectionService();
        $snapshot = (new PriceSelectionSnapshotService())->snapshot($original, $criteria, $selector->select($original, $criteria));
        $mutated = new PriceSetDTO('set-history', 'catalog:variant:703', [
            new PriceDefinitionDTO('price', 'set-history', 'catalog:variant:703', 'USD', 450, revision: 2),
        ], revision: 4);

        $this->expectException(PriceReplayMismatchException::class);
        (new PriceSelectionReplayService($selector))->replay($mutated, $snapshot);
    }
}
