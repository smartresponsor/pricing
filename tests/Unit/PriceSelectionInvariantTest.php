<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Unit;

use App\Pricing\DTO\PriceDefinitionDTO;
use App\Pricing\DTO\PriceListDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\Service\PriceSelectionService;
use PHPUnit\Framework\TestCase;

/** Exercises list, set, criteria, and rejection-reason invariants. */
final class PriceSelectionInvariantTest extends TestCase
{
    /** Proves malformed list state and malformed selection criteria are rejected immediately. */
    public function testListAndCriteriaRejectMalformedState(): void
    {
        foreach ([
            fn () => new PriceListDTO(''),
            fn () => new PriceListDTO('list', revision: 0),
            fn () => new PriceListDTO('list', context: ['region' => '']),
            fn () => new PriceListDTO(
                'list',
                startsAt: new \DateTimeImmutable('2026-09-20T11:00:00+00:00'),
                endsAt: new \DateTimeImmutable('2026-09-20T10:00:00+00:00'),
            ),
            fn () => new PriceSelectionCriteriaDTO('usd', 1, new \DateTimeImmutable()),
            fn () => new PriceSelectionCriteriaDTO('USD', 0, new \DateTimeImmutable()),
            fn () => new PriceSelectionCriteriaDTO('USD', 1, new \DateTimeImmutable(), ['region' => '']),
        ] as $factory) {
            try {
                $factory();
                self::fail('Expected malformed list or criteria to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** Proves PriceSet rejects duplicate and cross-boundary composition errors. */
    public function testPriceSetRejectsInvalidComposition(): void
    {
        $list = new PriceListDTO('list');

        foreach ([
            fn () => new PriceSetDTO('', 'ref', []),
            fn () => new PriceSetDTO('set', '', []),
            fn () => new PriceSetDTO('set', 'ref', [], revision: 0),
            fn () => new PriceSetDTO('set', 'ref', [], [$list, $list]),
            fn () => new PriceSetDTO('set', 'ref', [
                new PriceDefinitionDTO('price', 'other', 'ref', 'USD', 1),
            ]),
            fn () => new PriceSetDTO('set', 'ref', [
                new PriceDefinitionDTO('price', 'set', 'ref', 'USD', 1),
                new PriceDefinitionDTO('price', 'set', 'ref', 'USD', 2),
            ]),
            fn () => new PriceSetDTO('set', 'ref', [
                new PriceDefinitionDTO('price', 'set', 'ref', 'USD', 1, 'missing'),
            ]),
        ] as $factory) {
            try {
                $factory();
                self::fail('Expected malformed price set to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** Proves disabled, list-context, and price-context mismatches stay distinguishable. */
    public function testSelectionReportsStableContextAndLifecycleReasons(): void
    {
        $at = new \DateTimeImmutable('2026-09-20T12:00:00+00:00');
        $set = new PriceSetDTO(
            'set',
            'ref',
            [
                new PriceDefinitionDTO('disabled-price', 'set', 'ref', 'USD', 80, 'disabled'),
                new PriceDefinitionDTO('list-context', 'set', 'ref', 'USD', 70, 'regional'),
                new PriceDefinitionDTO('price-context', 'set', 'ref', 'USD', 60, context: ['channel' => 'store']),
                new PriceDefinitionDTO('fallback', 'set', 'ref', 'USD', 100),
            ],
            [
                new PriceListDTO('disabled', false),
                new PriceListDTO('regional', context: ['region' => 'eu']),
            ],
        );

        $result = (new PriceSelectionService())->select(
            $set,
            new PriceSelectionCriteriaDTO('USD', 1, $at, ['region' => 'us', 'channel' => 'web']),
        );

        self::assertSame('fallback', $result->selected->id);
        self::assertSame('price_list_inactive', $result->rejectedReasons['disabled-price']);
        self::assertSame('price_list_context_mismatch', $result->rejectedReasons['list-context']);
        self::assertSame('price_context_mismatch', $result->rejectedReasons['price-context']);
    }

    /** Proves an entirely inapplicable set fails instead of fabricating a fallback price. */
    public function testSelectionFailsWhenNoApplicablePriceExists(): void
    {
        $set = new PriceSetDTO('set', 'ref', [
            new PriceDefinitionDTO('eur', 'set', 'ref', 'EUR', 100),
        ]);

        $this->expectException(\RuntimeException::class);
        (new PriceSelectionService())->select(
            $set,
            new PriceSelectionCriteriaDTO('USD', 1, new \DateTimeImmutable('2026-09-20T12:00:00+00:00')),
        );
    }
}
