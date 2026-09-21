<?php

declare(strict_types=1);

namespace App\Pricing\Service;

use App\Currencing\ServiceInterface\CurrencyCodeValidatorInterface;
use App\Currencing\ServiceInterface\CurrencyPrecisionResolverInterface;
use App\Pricing\DTO\PriceCurrencyMetadataDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\ServiceInterface\PriceCurrencyValidationServiceInterface;

/**
 * Delegates supported-code and precision ownership to Currencing without duplicating its registry.
 */
final class PriceCurrencyValidationService implements PriceCurrencyValidationServiceInterface
{
    public function validate(
        PriceSetDTO $priceSet,
        PriceSelectionCriteriaDTO $criteria,
        CurrencyCodeValidatorInterface $codeValidator,
        CurrencyPrecisionResolverInterface $precisionResolver,
    ): PriceCurrencyMetadataDTO {
        $codeValidator->assertSupported($criteria->currencyCode);

        foreach ($priceSet->prices as $price) {
            $codeValidator->assertSupported($price->currencyCode);
        }

        $minorUnit = $precisionResolver->minorUnitFor($criteria->currencyCode);
        $factor = $precisionResolver->factorFor($criteria->currencyCode);

        return new PriceCurrencyMetadataDTO($criteria->currencyCode, $minorUnit, $factor);
    }
}
