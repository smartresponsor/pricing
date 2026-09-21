<?php

declare(strict_types=1);

namespace App\Pricing\DTO;

/**
 * Immutable reusable price definition with deterministic applicability metadata.
 *
 * Currency is represented as a canonical three-letter code only. Currency catalog
 * validation, precision policy, and conversion remain owned by Currencing/Exchanging.
 */
final readonly class PriceDefinitionDTO
{
    /** @param array<string, string> $context */
    public function __construct(
        public string $id,
        public string $priceSetId,
        public string $priceableReference,
        public string $currencyCode,
        public int $amountMinor,
        public ?string $priceListId = null,
        public int $minimumQuantity = 1,
        public ?int $maximumQuantity = null,
        public array $context = [],
        public ?\DateTimeImmutable $startsAt = null,
        public ?\DateTimeImmutable $endsAt = null,
        public ?int $referenceAmountMinor = null,
        public bool $taxIncluded = false,
        public int $revision = 1,
    ) {
        foreach ([
            'Price id' => $this->id,
            'Price set id' => $this->priceSetId,
            'Priceable reference' => $this->priceableReference,
        ] as $label => $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException($label.' must not be empty.');
            }
        }

        if (1 !== preg_match('/^[A-Z]{3}$/', $this->currencyCode)) {
            throw new \InvalidArgumentException('Currency code must be an uppercase three-letter code.');
        }
        if ($this->amountMinor < 0) {
            throw new \InvalidArgumentException('Price amount must not be negative.');
        }
        if ($this->minimumQuantity < 1) {
            throw new \InvalidArgumentException('Minimum quantity must be at least one.');
        }
        if (null !== $this->maximumQuantity && $this->maximumQuantity < $this->minimumQuantity) {
            throw new \InvalidArgumentException('Maximum quantity must be greater than or equal to minimum quantity.');
        }
        if (null !== $this->referenceAmountMinor && $this->referenceAmountMinor < 0) {
            throw new \InvalidArgumentException('Reference amount must not be negative.');
        }
        if ($this->revision < 1) {
            throw new \InvalidArgumentException('Price revision must be at least one.');
        }
        if (null !== $this->startsAt && null !== $this->endsAt && $this->startsAt >= $this->endsAt) {
            throw new \InvalidArgumentException('Price effective window start must be before its end.');
        }
        foreach ($this->context as $name => $value) {
            if ('' === trim((string) $name) || '' === trim($value)) {
                throw new \InvalidArgumentException('Price context names and values must not be empty.');
            }
        }
    }

    /** Determines whether this price is effective at the supplied instant. */
    public function isEffectiveAt(\DateTimeImmutable $at): bool
    {
        return (null === $this->startsAt || $at >= $this->startsAt)
            && (null === $this->endsAt || $at < $this->endsAt);
    }

    /** Determines whether the requested quantity falls inside this price tier. */
    public function supportsQuantity(int $quantity): bool
    {
        return $quantity >= $this->minimumQuantity
            && (null === $this->maximumQuantity || $quantity <= $this->maximumQuantity);
    }

    /** Returns whether all price-level contextual constraints match the supplied context.
     *
     * @param array<string, string> $context
     */
    public function matchesContext(array $context): bool
    {
        foreach ($this->context as $name => $value) {
            if (($context[$name] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    /** Returns the number of contextual constraints used for deterministic ranking. */
    public function contextSpecificity(): int
    {
        return count($this->context);
    }
}
