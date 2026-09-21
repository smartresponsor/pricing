<?php

declare(strict_types=1);

namespace App\Pricing\Service;

use App\Currencing\ServiceInterface\CurrencyCodeValidatorInterface;
use App\Currencing\ServiceInterface\CurrencyPrecisionResolverInterface;
use App\Pricing\DTO\PriceQuoteDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\ServiceInterface\PriceCurrencyValidationServiceInterface;
use App\Pricing\ServiceInterface\PriceQuoteServiceInterface;
use App\Pricing\ServiceInterface\PriceSelectionServiceInterface;
use App\Pricing\ServiceInterface\PriceSelectionSnapshotServiceInterface;

/**
 * Composes Pricing selection, Currencing validation, and immutable provenance into one quote.
 */
final class PriceQuoteService implements PriceQuoteServiceInterface
{
    public function __construct(
        private PriceSelectionServiceInterface $selectionService,
        private PriceSelectionSnapshotServiceInterface $snapshotService,
        private PriceCurrencyValidationServiceInterface $currencyValidationService,
    ) {
    }

    public function quote(
        PriceSetDTO $priceSet,
        PriceSelectionCriteriaDTO $criteria,
        CurrencyCodeValidatorInterface $codeValidator,
        CurrencyPrecisionResolverInterface $precisionResolver,
    ): PriceQuoteDTO {
        $currency = $this->currencyValidationService->validate(
            $priceSet,
            $criteria,
            $codeValidator,
            $precisionResolver,
        );
        $result = $this->selectionService->select($priceSet, $criteria);
        $snapshot = $this->snapshotService->snapshot($priceSet, $criteria, $result);

        return new PriceQuoteDTO($snapshot, $currency);
    }
}
