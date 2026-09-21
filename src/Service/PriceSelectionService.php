<?php

declare(strict_types=1);

namespace App\Pricing\Service;

use App\Pricing\DTO\PriceDefinitionDTO;
use App\Pricing\DTO\PriceListDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSelectionResultDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\ServiceInterface\PriceSelectionServiceInterface;

/**
 * Applies deterministic price-list, currency, window, quantity, and context selection.
 */
final class PriceSelectionService implements PriceSelectionServiceInterface
{
    /** Selects the best applicable price and records why definitions were rejected. */
    public function select(PriceSetDTO $priceSet, PriceSelectionCriteriaDTO $criteria): PriceSelectionResultDTO
    {
        $candidates = [];
        $rejected = [];

        foreach ($priceSet->prices as $price) {
            $reason = $this->rejectionReason($priceSet, $price, $criteria);
            if (null !== $reason) {
                $rejected[$price->id] = $reason;
                continue;
            }
            $candidates[] = $price;
        }

        if ([] === $candidates) {
            throw new \RuntimeException('No applicable price exists for the supplied selection criteria.');
        }

        usort($candidates, fn (PriceDefinitionDTO $left, PriceDefinitionDTO $right): int => $this->compare($priceSet, $left, $right));
        $selected = $candidates[0];
        $candidateIds = array_map(static fn (PriceDefinitionDTO $candidate): string => $candidate->id, $candidates);
        $list = $priceSet->priceList($selected->priceListId);

        return new PriceSelectionResultDTO(
            selected: $selected,
            candidateIds: $candidateIds,
            rejectedReasons: $rejected,
            explanation: sprintf(
                'Selected %s using listPriority=%d, contextSpecificity=%d, minimumQuantity=%d, amountMinor=%d, revision=%d, deterministicIdTieBreak=%s.',
                $selected->id,
                null === $list ? 0 : $list->priority,
                $selected->contextSpecificity() + ($list?->contextSpecificity() ?? 0),
                $selected->minimumQuantity,
                $selected->amountMinor,
                $selected->revision,
                $selected->id,
            ),
            priceSetRevision: $priceSet->revision,
        );
    }

    private function rejectionReason(
        PriceSetDTO $priceSet,
        PriceDefinitionDTO $price,
        PriceSelectionCriteriaDTO $criteria,
    ): ?string {
        if ($price->currencyCode !== $criteria->currencyCode) {
            return 'currency_mismatch';
        }
        if (!$price->isEffectiveAt($criteria->at)) {
            return 'price_outside_effective_window';
        }
        if (!$price->supportsQuantity($criteria->quantity)) {
            return 'quantity_outside_tier';
        }
        if (!$price->matchesContext($criteria->context)) {
            return 'price_context_mismatch';
        }

        $list = $priceSet->priceList($price->priceListId);
        if ($list instanceof PriceListDTO && !$list->isEffectiveAt($criteria->at)) {
            return 'price_list_inactive';
        }
        if ($list instanceof PriceListDTO && !$list->matchesContext($criteria->context)) {
            return 'price_list_context_mismatch';
        }

        return null;
    }

    private function compare(PriceSetDTO $priceSet, PriceDefinitionDTO $left, PriceDefinitionDTO $right): int
    {
        $leftList = $priceSet->priceList($left->priceListId);
        $rightList = $priceSet->priceList($right->priceListId);
        $comparisons = [
            (null === $rightList ? 0 : $rightList->priority) <=> (null === $leftList ? 0 : $leftList->priority),
            ($right->contextSpecificity() + ($rightList?->contextSpecificity() ?? 0))
                <=> ($left->contextSpecificity() + ($leftList?->contextSpecificity() ?? 0)),
            $right->minimumQuantity <=> $left->minimumQuantity,
            $left->amountMinor <=> $right->amountMinor,
            $right->revision <=> $left->revision,
        ];

        foreach ($comparisons as $comparison) {
            if (0 !== $comparison) {
                return $comparison;
            }
        }

        return $left->id <=> $right->id;
    }
}
