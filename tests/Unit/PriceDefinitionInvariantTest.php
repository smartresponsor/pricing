<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Unit;

use App\Pricing\DTO\PriceDefinitionDTO;
use PHPUnit\Framework\TestCase;

/** Exercises value, identity, window, tier, and context invariants on reusable price definitions. */
final class PriceDefinitionInvariantTest extends TestCase
{
    /** Proves zero is an explicit valid price while negative monetary values are rejected. */
    public function testZeroPriceIsValidAndNegativeAmountsAreRejected(): void
    {
        $zero = new PriceDefinitionDTO('free', 'set', 'catalog:variant:free', 'USD', 0);
        self::assertSame(0, $zero->amountMinor);

        $this->expectException(\InvalidArgumentException::class);
        new PriceDefinitionDTO('negative', 'set', 'catalog:variant:negative', 'USD', -1);
    }

    /** Proves malformed identity, currency, revision, reference amount, window, and context fail early. */
    public function testDefinitionRejectsMalformedState(): void
    {
        foreach ([
            fn () => new PriceDefinitionDTO('', 'set', 'ref', 'USD', 1),
            fn () => new PriceDefinitionDTO('id', '', 'ref', 'USD', 1),
            fn () => new PriceDefinitionDTO('id', 'set', '', 'USD', 1),
            fn () => new PriceDefinitionDTO('id', 'set', 'ref', 'usd', 1),
            fn () => new PriceDefinitionDTO('id', 'set', 'ref', 'USD', 1, revision: 0),
            fn () => new PriceDefinitionDTO('id', 'set', 'ref', 'USD', 1, referenceAmountMinor: -1),
            fn () => new PriceDefinitionDTO(
                'id',
                'set',
                'ref',
                'USD',
                1,
                startsAt: new \DateTimeImmutable('2026-09-21T00:00:00+00:00'),
                endsAt: new \DateTimeImmutable('2026-09-21T00:00:00+00:00'),
            ),
            fn () => new PriceDefinitionDTO('id', 'set', 'ref', 'USD', 1, context: ['region' => '']),
        ] as $factory) {
            try {
                $factory();
                self::fail('Expected invalid price definition to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** Proves effective windows are start-inclusive/end-exclusive and quantity tiers include both boundaries. */
    public function testWindowAndTierBoundariesAreExact(): void
    {
        $price = new PriceDefinitionDTO(
            'windowed',
            'set',
            'ref',
            'USD',
            100,
            minimumQuantity: 2,
            maximumQuantity: 5,
            startsAt: new \DateTimeImmutable('2026-09-20T10:00:00+00:00'),
            endsAt: new \DateTimeImmutable('2026-09-20T11:00:00+00:00'),
        );

        self::assertTrue($price->isEffectiveAt(new \DateTimeImmutable('2026-09-20T10:00:00+00:00')));
        self::assertFalse($price->isEffectiveAt(new \DateTimeImmutable('2026-09-20T11:00:00+00:00')));
        self::assertTrue($price->supportsQuantity(2));
        self::assertTrue($price->supportsQuantity(5));
        self::assertFalse($price->supportsQuantity(1));
        self::assertFalse($price->supportsQuantity(6));
        self::assertTrue($price->matchesContext([]));
        self::assertSame(0, $price->contextSpecificity());
    }
}
