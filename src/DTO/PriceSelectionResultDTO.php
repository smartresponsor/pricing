<?php

declare(strict_types=1);

namespace App\Pricing\DTO;

/**
 * Auditable deterministic result containing the selected price and provenance.
 */
final readonly class PriceSelectionResultDTO
{
    /**
     * @param list<string>          $candidateIds
     * @param array<string, string> $rejectedReasons
     */
    public function __construct(
        public PriceDefinitionDTO $selected,
        public array $candidateIds,
        public array $rejectedReasons,
        public string $explanation,
        public int $priceSetRevision,
    ) {
    }
}
