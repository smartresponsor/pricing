<?php

declare(strict_types=1);

namespace App\Pricing\ServiceInterface;

use App\Currencing\ServiceInterface\CurrencyCodeValidatorInterface;
use App\Currencing\ServiceInterface\CurrencyPrecisionResolverInterface;
use App\Pricing\DTO\PriceCurrencyMetadataDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSetDTO;

/**
 * Validates Pricing currency semantics through Currencing-owned public contracts.
 */
interface PriceCurrencyValidationServiceInterface
{
    /** Validates all defined/selected currencies and returns authoritative precision metadata. */
    public function validate(
        PriceSetDTO $priceSet,
        PriceSelectionCriteriaDTO $criteria,
        CurrencyCodeValidatorInterface $codeValidator,
        CurrencyPrecisionResolverInterface $precisionResolver,
    ): PriceCurrencyMetadataDTO;
}
