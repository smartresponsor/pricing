<?php

declare(strict_types=1);

namespace App\Pricing\ServiceInterface;

use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSelectionResultDTO;
use App\Pricing\DTO\PriceSetDTO;

/**
 * Selects one applicable reusable price without promotions, tax calculation, FX, or order totals.
 */
interface PriceSelectionServiceInterface
{
    /** Produces a deterministic, replayable selection with auditable candidate evidence. */
    public function select(PriceSetDTO $priceSet, PriceSelectionCriteriaDTO $criteria): PriceSelectionResultDTO;
}
