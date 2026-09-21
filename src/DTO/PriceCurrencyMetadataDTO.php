<?php

declare(strict_types=1);

namespace App\Pricing\DTO;

/**
 * Currencing-backed currency metadata attached to a validated Pricing decision.
 */
final readonly class PriceCurrencyMetadataDTO
{
    public function __construct(
        public string $currencyCode,
        public int $minorUnit,
        public int $factor,
    ) {
        if (1 !== preg_match('/^[A-Z]{3}$/', $this->currencyCode)) {
            throw new \InvalidArgumentException('Validated currency code must be an uppercase three-letter code.');
        }
        if ($this->minorUnit < 0 || $this->minorUnit > 8) {
            throw new \InvalidArgumentException('Currency minor unit must be between zero and eight.');
        }
        if ($this->factor !== 10 ** $this->minorUnit) {
            throw new \InvalidArgumentException('Currency factor must match the resolved minor unit.');
        }
    }
}
