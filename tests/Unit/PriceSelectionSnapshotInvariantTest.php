<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Unit;

use App\Pricing\DTO\PriceSelectionSnapshotDTO;
use PHPUnit\Framework\TestCase;

/** Verifies every persisted selection-snapshot invariant independently. */
final class PriceSelectionSnapshotInvariantTest extends TestCase
{
    /** Proves malformed historical provenance is rejected before it reaches a downstream aggregate. */
    public function testSnapshotRejectsMalformedProvenance(): void
    {
        foreach ([
            fn () => $this->snapshot(priceSetId: ''),
            fn () => $this->snapshot(priceableReference: ''),
            fn () => $this->snapshot(priceId: ''),
            fn () => $this->snapshot(explanation: ''),
            fn () => $this->snapshot(priceSetRevision: 0),
            fn () => $this->snapshot(priceRevision: 0),
            fn () => $this->snapshot(priceListId: 'list', priceListRevision: null),
            fn () => $this->snapshot(priceListId: 'list', priceListRevision: 0),
            fn () => $this->snapshot(priceListId: null, priceListRevision: 1),
            fn () => $this->snapshot(currencyCode: 'usd'),
            fn () => $this->snapshot(quantity: 0),
            fn () => $this->snapshot(amountMinor: -1),
            fn () => $this->snapshot(referenceAmountMinor: -1),
            fn () => $this->snapshot(context: ['' => 'us']),
            fn () => $this->snapshot(context: ['region' => '']),
        ] as $factory) {
            try {
                $factory();
                self::fail('Expected malformed selection snapshot to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** Proves a valid snapshot reconstructs the exact replay criteria. */
    public function testSnapshotBuildsExactReplayCriteria(): void
    {
        $selectedAt = new \DateTimeImmutable('2026-09-21T12:00:00+00:00');
        $snapshot = $this->snapshot(
            priceListId: 'vip',
            priceListRevision: 3,
            referenceAmountMinor: 1500,
            taxIncluded: true,
            selectedAt: $selectedAt,
            context: ['region' => 'us'],
        );

        $criteria = $snapshot->criteria();

        self::assertSame('USD', $criteria->currencyCode);
        self::assertSame(2, $criteria->quantity);
        self::assertSame($selectedAt, $criteria->at);
        self::assertSame(['region' => 'us'], $criteria->context);
    }

    /** @param array<string, string> $context */
    private function snapshot(
        string $priceSetId = 'set',
        int $priceSetRevision = 2,
        string $priceableReference = 'catalog:variant:1',
        string $priceId = 'price',
        int $priceRevision = 4,
        ?string $priceListId = null,
        ?int $priceListRevision = null,
        string $currencyCode = 'USD',
        int $quantity = 2,
        int $amountMinor = 1200,
        ?int $referenceAmountMinor = null,
        bool $taxIncluded = false,
        ?\DateTimeImmutable $selectedAt = null,
        array $context = [],
        string $explanation = 'selected',
    ): PriceSelectionSnapshotDTO {
        return new PriceSelectionSnapshotDTO(
            priceSetId: $priceSetId,
            priceSetRevision: $priceSetRevision,
            priceableReference: $priceableReference,
            priceId: $priceId,
            priceRevision: $priceRevision,
            priceListId: $priceListId,
            priceListRevision: $priceListRevision,
            currencyCode: $currencyCode,
            quantity: $quantity,
            amountMinor: $amountMinor,
            referenceAmountMinor: $referenceAmountMinor,
            taxIncluded: $taxIncluded,
            selectedAt: $selectedAt ?? new \DateTimeImmutable('2026-09-21T12:00:00+00:00'),
            context: $context,
            explanation: $explanation,
        );
    }
}
