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
        self::assertIdentity($this->id, $this->priceSetId, $this->priceableReference);
        self::assertCurrencyAndAmounts($this->currencyCode, $this->amountMinor, $this->referenceAmountMinor);
        self::assertQuantityTier($this->minimumQuantity, $this->maximumQuantity);
        self::assertRevisionAndWindow($this->revision, $this->startsAt, $this->endsAt);
        self::assertContext($this->context);
    }

    private static function assertIdentity(string $id, string $priceSetId, string $priceableReference): void
    {
        foreach (['Price id' => $id, 'Price set id' => $priceSetId, 'Priceable reference' => $priceableReference] as $label => $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException($label.' must not be empty.');
            }
        }
    }

    private static function assertCurrencyAndAmounts(string $currencyCode, int $amountMinor, ?int $referenceAmountMinor): void
    {
        if (1 !== preg_match('/^[A-Z]{3}$/', $currencyCode)) {
            throw new \InvalidArgumentException('Currency code must be an uppercase three-letter code.');
        }
        if ($amountMinor < 0) {
            throw new \InvalidArgumentException('Price amount must not be negative.');
        }
        if (null !== $referenceAmountMinor && $referenceAmountMinor < 0) {
            throw new \InvalidArgumentException('Reference amount must not be negative.');
        }
    }

    private static function assertQuantityTier(int $minimumQuantity, ?int $maximumQuantity): void
    {
        if ($minimumQuantity < 1) {
            throw new \InvalidArgumentException('Minimum quantity must be at least one.');
        }
        if (null !== $maximumQuantity && $maximumQuantity < $minimumQuantity) {
            throw new \InvalidArgumentException('Maximum quantity must be greater than or equal to minimum quantity.');
        }
    }

    private static function assertRevisionAndWindow(int $revision, ?\DateTimeImmutable $startsAt, ?\DateTimeImmutable $endsAt): void
    {
        if ($revision < 1) {
            throw new \InvalidArgumentException('Price revision must be at least one.');
        }
        if (null !== $startsAt && null !== $endsAt && $startsAt >= $endsAt) {
            throw new \InvalidArgumentException('Price effective window start must be before its end.');
        }
    }

    /** @param array<string, string> $context */
    private static function assertContext(array $context): void
    {
        foreach ($context as $name => $value) {
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
