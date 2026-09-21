<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Unit;

use App\Pricing\DTO\PriceCurrencyMetadataDTO;
use App\Pricing\DTO\PriceDefinitionDTO;
use App\Pricing\DTO\PriceListDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSetDTO;
use PHPUnit\Framework\TestCase;

/** Completes alternate-branch coverage for immutable Pricing value contracts. */
final class PriceDtoBoundaryTest extends TestCase
{
    /** Proves currency metadata validates code shape, precision bounds, and factor parity independently. */
    public function testCurrencyMetadataValidatesEveryInvariantBranch(): void
    {
        $valid = new PriceCurrencyMetadataDTO('USD', 2, 100);
        self::assertSame(100, $valid->factor);

        foreach ([
            static fn () => new PriceCurrencyMetadataDTO('usd', 2, 100),
            static fn () => new PriceCurrencyMetadataDTO('USD', -1, 1),
            static fn () => new PriceCurrencyMetadataDTO('USD', 9, 1_000_000_000),
        ] as $factory) {
            try {
                $factory();
                self::fail('Expected invalid currency metadata to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** Proves open-ended price windows, unbounded tiers, and contextual matching use both alternatives. */
    public function testPriceDefinitionOpenBoundsAndContextBranches(): void
    {
        $price = new PriceDefinitionDTO(
            'contextual',
            'set',
            'ref',
            'USD',
            100,
            minimumQuantity: 2,
            context: ['region' => 'us'],
        );
        $at = new \DateTimeImmutable('2026-09-21T12:00:00+00:00');

        self::assertTrue($price->isEffectiveAt($at));
        self::assertTrue($price->supportsQuantity(2));
        self::assertTrue($price->supportsQuantity(10_000));
        self::assertTrue($price->matchesContext(['region' => 'us']));
        self::assertFalse($price->matchesContext(['region' => 'eu']));
        self::assertSame(1, $price->contextSpecificity());

        foreach ([
            static fn () => new PriceDefinitionDTO('id', 'set', 'ref', 'USD', 1, minimumQuantity: 0),
            static fn () => new PriceDefinitionDTO('id', 'set', 'ref', 'USD', 1, context: ['' => 'us']),
        ] as $factory) {
            try {
                $factory();
                self::fail('Expected invalid price definition branch to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** Proves one-sided effective windows cover both start-only and end-only definitions. */
    public function testDefinitionOneSidedWindowBranches(): void
    {
        $at = new \DateTimeImmutable('2026-09-21T12:00:00+00:00');
        $endOnly = new PriceDefinitionDTO(
            'end-only',
            'set',
            'ref',
            'USD',
            100,
            endsAt: new \DateTimeImmutable('2026-09-21T13:00:00+00:00'),
        );
        $startOnly = new PriceDefinitionDTO(
            'start-only',
            'set',
            'ref',
            'USD',
            100,
            startsAt: new \DateTimeImmutable('2026-09-21T13:00:00+00:00'),
        );

        self::assertTrue($endOnly->isEffectiveAt($at));
        self::assertFalse($endOnly->isEffectiveAt(new \DateTimeImmutable('2026-09-21T13:00:00+00:00')));
        self::assertFalse($startOnly->isEffectiveAt($at));
        self::assertTrue($startOnly->isEffectiveAt(new \DateTimeImmutable('2026-09-21T13:00:00+00:00')));
    }

    /** Proves list context-name validation and open-ended lifecycle branches are explicit. */
    public function testPriceListOpenBoundsAndContextBranches(): void
    {
        $list = new PriceListDTO('regional', context: ['region' => 'us']);
        $at = new \DateTimeImmutable('2026-09-21T12:00:00+00:00');

        self::assertTrue($list->isEffectiveAt($at));
        self::assertTrue($list->matchesContext(['region' => 'us']));
        self::assertFalse($list->matchesContext(['region' => 'eu']));
        self::assertSame(1, $list->contextSpecificity());

        $endOnly = new PriceListDTO(
            'end-only',
            endsAt: new \DateTimeImmutable('2026-09-21T13:00:00+00:00'),
        );
        $startOnly = new PriceListDTO(
            'start-only',
            startsAt: new \DateTimeImmutable('2026-09-21T13:00:00+00:00'),
        );
        self::assertTrue($endOnly->isEffectiveAt($at));
        self::assertFalse($startOnly->isEffectiveAt($at));

        $this->expectException(\InvalidArgumentException::class);
        new PriceListDTO('regional', context: ['' => 'us']);
    }

    /** Proves selection criteria reject an empty context name separately from an empty value. */
    public function testCriteriaContextNameAndValidContextBranches(): void
    {
        $criteria = new PriceSelectionCriteriaDTO(
            'USD',
            1,
            new \DateTimeImmutable('2026-09-21T12:00:00+00:00'),
            ['region' => 'us'],
        );
        self::assertSame('us', $criteria->context['region']);

        $this->expectException(\InvalidArgumentException::class);
        new PriceSelectionCriteriaDTO(
            'USD',
            1,
            new \DateTimeImmutable('2026-09-21T12:00:00+00:00'),
            ['' => 'us'],
        );
    }

    /** Proves PriceSet validates both identity coordinates and exposes null/found/missing list lookup. */
    public function testPriceSetReferenceBranchAndListLookup(): void
    {
        $list = new PriceListDTO('list');
        $set = new PriceSetDTO(
            'set',
            'ref',
            [new PriceDefinitionDTO('price', 'set', 'ref', 'USD', 100, 'list')],
            [$list],
        );

        self::assertNull($set->priceList(null));
        self::assertSame($list, $set->priceList('list'));
        self::assertNull($set->priceList('missing'));

        $this->expectException(\InvalidArgumentException::class);
        new PriceSetDTO(
            'set',
            'ref',
            [new PriceDefinitionDTO('price', 'set', 'other-ref', 'USD', 100)],
        );
    }
}
