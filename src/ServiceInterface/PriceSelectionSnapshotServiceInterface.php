<?php

declare(strict_types=1);

namespace App\Pricing\ServiceInterface;

use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSelectionResultDTO;
use App\Pricing\DTO\PriceSelectionSnapshotDTO;
use App\Pricing\DTO\PriceSetDTO;

/**
 * Captures a deterministic Pricing decision for downstream persistence and audit.
 */
interface PriceSelectionSnapshotServiceInterface
{
    /** Creates immutable provenance without introducing cart or order ownership into Pricing. */
    public function snapshot(
        PriceSetDTO $priceSet,
        PriceSelectionCriteriaDTO $criteria,
        PriceSelectionResultDTO $result,
    ): PriceSelectionSnapshotDTO;
}
