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
        self::assertIdentity($this->priceSetId, $this->priceableReference, $this->priceId, $this->explanation);
        self::assertRevisions($this->priceSetRevision, $this->priceRevision, $this->priceListId, $this->priceListRevision);
        self::assertSelectionValues($this->currencyCode, $this->quantity, $this->amountMinor, $this->referenceAmountMinor);
        self::assertContext($this->context);
    }

    private static function assertIdentity(string $priceSetId, string $priceableReference, string $priceId, string $explanation): void
    {
        foreach ([
            'Price set id' => $priceSetId,
            'Priceable reference' => $priceableReference,
            'Price id' => $priceId,
            'Selection explanation' => $explanation,
        ] as $label => $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException($label.' must not be empty.');
            }
        }
    }

    private static function assertRevisions(int $priceSetRevision, int $priceRevision, ?string $priceListId, ?int $priceListRevision): void
    {
        if ($priceSetRevision < 1 || $priceRevision < 1) {
            throw new \InvalidArgumentException('Price set and price revisions must be at least one.');
        }
        if (null !== $priceListId && (null === $priceListRevision || $priceListRevision < 1)) {
            throw new \InvalidArgumentException('Price list revision is required when a price list id is present.');
        }
        if (null === $priceListId && null !== $priceListRevision) {
            throw new \InvalidArgumentException('Price list revision requires a price list id.');
        }
    }

    private static function assertSelectionValues(string $currencyCode, int $quantity, int $amountMinor, ?int $referenceAmountMinor): void
    {
        if (1 !== preg_match('/^[A-Z]{3}$/', $currencyCode)) {
            throw new \InvalidArgumentException('Snapshot currency code must be an uppercase three-letter code.');
        }
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Snapshot quantity must be at least one.');
        }
        if ($amountMinor < 0 || (null !== $referenceAmountMinor && $referenceAmountMinor < 0)) {
            throw new \InvalidArgumentException('Snapshot monetary amounts must not be negative.');
        }
    }

    /** @param array<string, string> $context */
    private static function assertContext(array $context): void
    {
        foreach ($context as $name => $value) {
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
