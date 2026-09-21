<?php

declare(strict_types=1);

namespace App\Pricing\DTO;

/**
 * Immutable provenance snapshot suitable for cart/order persistence and historical replay.
 */
final readonly class PriceSelectionSnapshotDTO
{
    /** @param array<string, string> $context */
    public function __construct(
        public string $priceSetId,
        public int $priceSetRevision,
        public string $priceableReference,
        public string $priceId,
        public int $priceRevision,
        public ?string $priceListId,
        public ?int $priceListRevision,
        public string $currencyCode,
        public int $quantity,
        public int $amountMinor,
        public ?int $referenceAmountMinor,
        public bool $taxIncluded,
        public \DateTimeImmutable $selectedAt,
        public array $context,
        public string $explanation,
    ) {
        foreach ([
            'Price set id' => $this->priceSetId,
            'Priceable reference' => $this->priceableReference,
            'Price id' => $this->priceId,
            'Selection explanation' => $this->explanation,
        ] as $label => $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException($label.' must not be empty.');
            }
        }
        if ($this->priceSetRevision < 1 || $this->priceRevision < 1) {
            throw new \InvalidArgumentException('Price set and price revisions must be at least one.');
        }
        if (null !== $this->priceListId && (null === $this->priceListRevision || $this->priceListRevision < 1)) {
            throw new \InvalidArgumentException('Price list revision is required when a price list id is present.');
        }
        if (null === $this->priceListId && null !== $this->priceListRevision) {
            throw new \InvalidArgumentException('Price list revision requires a price list id.');
        }
        if (1 !== preg_match('/^[A-Z]{3}$/', $this->currencyCode)) {
            throw new \InvalidArgumentException('Snapshot currency code must be an uppercase three-letter code.');
        }
        if ($this->quantity < 1) {
            throw new \InvalidArgumentException('Snapshot quantity must be at least one.');
        }
        if ($this->amountMinor < 0 || (null !== $this->referenceAmountMinor && $this->referenceAmountMinor < 0)) {
            throw new \InvalidArgumentException('Snapshot monetary amounts must not be negative.');
        }
        foreach ($this->context as $name => $value) {
            if ('' === trim((string) $name) || '' === trim($value)) {
                throw new \InvalidArgumentException('Snapshot context names and values must not be empty.');
            }
        }
    }

    /** Rebuilds the exact selection criteria used by the historical decision. */
    public function criteria(): PriceSelectionCriteriaDTO
    {
        return new PriceSelectionCriteriaDTO($this->currencyCode, $this->quantity, $this->selectedAt, $this->context);
    }
}
