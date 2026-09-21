<?php

declare(strict_types=1);

namespace App\Pricing\ServiceInterface;

use App\Currencing\ServiceInterface\CurrencyCodeValidatorInterface;
use App\Currencing\ServiceInterface\CurrencyPrecisionResolverInterface;
use App\Pricing\DTO\PriceQuoteDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSetDTO;

/**
 * Produces the canonical typed Pricing output consumed by neighboring commerce components.
 */
interface PriceQuoteServiceInterface
{
    /** Selects, validates, snapshots, and returns one deterministic reusable price quote. */
    public function quote(
        PriceSetDTO $priceSet,
        PriceSelectionCriteriaDTO $criteria,
        CurrencyCodeValidatorInterface $codeValidator,
        CurrencyPrecisionResolverInterface $precisionResolver,
    ): PriceQuoteDTO;
}
