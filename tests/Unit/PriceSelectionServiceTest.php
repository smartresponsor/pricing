<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Unit;

use App\Pricing\DTO\PriceDefinitionDTO;
use App\Pricing\DTO\PriceListDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\Service\PriceSelectionService;
use PHPUnit\Framework\TestCase;

/** Verifies deterministic reusable price selection and its boundary invariants. */
final class PriceSelectionServiceTest extends TestCase
{
    /** Proves contextual list priority, tier specificity, and candidate evidence are deterministic. */
    public function testSelectsContextualTierDeterministically(): void
    {
        $at = new \DateTimeImmutable('2026-09-20T12:00:00+00:00');
        $list = new PriceListDTO(
            id: 'b2b',
            priority: 50,
            context: ['customerGroup' => 'b2b'],
            startsAt: new \DateTimeImmutable('2026-09-01T00:00:00+00:00'),
            endsAt: new \DateTimeImmutable('2026-10-01T00:00:00+00:00'),
        );
        $set = new PriceSetDTO(
            id: 'set-1',
            priceableReference: 'catalog:variant:42',
            prices: [
                new PriceDefinitionDTO('default', 'set-1', 'catalog:variant:42', 'USD', 1200),
                new PriceDefinitionDTO('b2b-base', 'set-1', 'catalog:variant:42', 'USD', 1000, 'b2b'),
                new PriceDefinitionDTO('b2b-tier', 'set-1', 'catalog:variant:42', 'USD', 800, 'b2b', 10, null, ['region' => 'us']),
            ],
            priceLists: [$list],
            revision: 7,
        );

        $result = (new PriceSelectionService())->select(
            $set,
            new PriceSelectionCriteriaDTO('USD', 12, $at, ['customerGroup' => 'b2b', 'region' => 'us']),
        );

        self::assertSame('b2b-tier', $result->selected->id);
        self::assertSame(['b2b-tier', 'b2b-base', 'default'], $result->candidateIds);
        self::assertSame(7, $result->priceSetRevision);
        self::assertStringContainsString('deterministicIdTieBreak=b2b-tier', $result->explanation);
    }

    /** Proves currency, time-window, and quantity mismatches are reported rather than converted. */
    public function testRejectsInapplicablePricesWithStableReasons(): void
    {
        $at = new \DateTimeImmutable('2026-09-20T12:00:00+00:00');
        $set = new PriceSetDTO(
            'set-2',
            'retail:offer:9',
            [
                new PriceDefinitionDTO('wrong-currency', 'set-2', 'retail:offer:9', 'EUR', 900),
                new PriceDefinitionDTO('future', 'set-2', 'retail:offer:9', 'USD', 900, startsAt: new \DateTimeImmutable('2026-10-01T00:00:00+00:00')),
                new PriceDefinitionDTO('bulk', 'set-2', 'retail:offer:9', 'USD', 700, minimumQuantity: 20),
                new PriceDefinitionDTO('selected', 'set-2', 'retail:offer:9', 'USD', 1000, referenceAmountMinor: 1200, taxIncluded: true, revision: 2),
            ],
        );

        $result = (new PriceSelectionService())->select($set, new PriceSelectionCriteriaDTO('USD', 2, $at));

        self::assertSame('selected', $result->selected->id);
        self::assertSame(1200, $result->selected->referenceAmountMinor);
        self::assertTrue($result->selected->taxIncluded);
        self::assertSame('currency_mismatch', $result->rejectedReasons['wrong-currency']);
        self::assertSame('price_outside_effective_window', $result->rejectedReasons['future']);
        self::assertSame('quantity_outside_tier', $result->rejectedReasons['bulk']);
    }

    /** Proves overlapping equal candidates resolve by stable id after all business ranking keys tie. */
    public function testStableIdTieBreakMakesOverlappingWindowsReplayable(): void
    {
        $at = new \DateTimeImmutable('2026-09-20T12:00:00+00:00');
        $set = new PriceSetDTO(
            'set-3',
            'catalog:variant:99',
            [
                new PriceDefinitionDTO('price-b', 'set-3', 'catalog:variant:99', 'USD', 500),
                new PriceDefinitionDTO('price-a', 'set-3', 'catalog:variant:99', 'USD', 500),
            ],
        );

        $result = (new PriceSelectionService())->select($set, new PriceSelectionCriteriaDTO('USD', 1, $at));
        self::assertSame('price-a', $result->selected->id);
        self::assertSame(['price-a', 'price-b'], $result->candidateIds);
    }

    /** Proves malformed quantity tiers fail before selection. */
    public function testDefinitionRejectsInvalidTier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PriceDefinitionDTO('invalid', 'set-4', 'catalog:variant:100', 'USD', 500, minimumQuantity: 10, maximumQuantity: 5);
    }
}
