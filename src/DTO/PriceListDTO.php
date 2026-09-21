<?php

declare(strict_types=1);

namespace App\Pricing\DTO;

/**
 * Immutable price-list lifecycle and contextual applicability contract.
 */
final readonly class PriceListDTO
{
    /** @param array<string, string> $context */
    public function __construct(
        public string $id,
        public bool $enabled = true,
        public int $priority = 0,
        public array $context = [],
        public ?\DateTimeImmutable $startsAt = null,
        public ?\DateTimeImmutable $endsAt = null,
        public int $revision = 1,
    ) {
        if ('' === trim($this->id)) {
            throw new \InvalidArgumentException('Price list id must not be empty.');
        }
        if ($this->revision < 1) {
            throw new \InvalidArgumentException('Price list revision must be at least one.');
        }
        if (null !== $this->startsAt && null !== $this->endsAt && $this->startsAt >= $this->endsAt) {
            throw new \InvalidArgumentException('Price list effective window start must be before its end.');
        }
        foreach ($this->context as $name => $value) {
            if ('' === trim((string) $name) || '' === trim($value)) {
                throw new \InvalidArgumentException('Price list context names and values must not be empty.');
            }
        }
    }

    /** Returns whether the price list lifecycle allows selection at this instant. */
    public function isEffectiveAt(\DateTimeImmutable $at): bool
    {
        return $this->enabled
            && (null === $this->startsAt || $at >= $this->startsAt)
            && (null === $this->endsAt || $at < $this->endsAt);
    }

    /** @param array<string, string> $context */
    public function matchesContext(array $context): bool
    {
        foreach ($this->context as $name => $value) {
            if (($context[$name] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    /** Returns the number of list-level contextual constraints. */
    public function contextSpecificity(): int
    {
        return count($this->context);
    }
}
