<?php

declare(strict_types=1);

namespace App\Pricing\DTO;

/**
 * Immutable selection input for deterministic replay of a pricing decision.
 */
final readonly class PriceSelectionCriteriaDTO
{
    /** @param array<string, string> $context */
    public function __construct(
        public string $currencyCode,
        public int $quantity,
        public \DateTimeImmutable $at,
        public array $context = [],
    ) {
        if (1 !== preg_match('/^[A-Z]{3}$/', $this->currencyCode)) {
            throw new \InvalidArgumentException('Selection currency code must be an uppercase three-letter code.');
        }
        if ($this->quantity < 1) {
            throw new \InvalidArgumentException('Selection quantity must be at least one.');
        }
        foreach ($this->context as $name => $value) {
            if ('' === trim((string) $name)) {
                throw new \InvalidArgumentException('Selection context names and values must not be empty.');
            }
            if ('' === trim($value)) {
                throw new \InvalidArgumentException('Selection context names and values must not be empty.');
            }
        }
    }
}
