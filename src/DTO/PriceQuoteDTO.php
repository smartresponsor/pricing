<?php

declare(strict_types=1);

namespace App\Pricing\DTO;

/**
 * Typed outbound Pricing contract for Cataloging/Retailing/Carting/Ordering consumers.
 */
final readonly class PriceQuoteDTO
{
    public function __construct(
        public PriceSelectionSnapshotDTO $snapshot,
        public PriceCurrencyMetadataDTO $currency,
    ) {
        if ($this->snapshot->currencyCode !== $this->currency->currencyCode) {
            throw new \InvalidArgumentException('Quote currency metadata must match the pricing snapshot currency.');
        }
    }

    /** Returns the selected effective amount in Currencing-compatible minor units. */
    public function amountMinor(): int
    {
        return $this->snapshot->amountMinor;
    }

    /** Returns the opaque external priceable-resource reference owned by the source component. */
    public function priceableReference(): string
    {
        return $this->snapshot->priceableReference;
    }
}
