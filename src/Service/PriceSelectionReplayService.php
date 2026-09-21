<?php

declare(strict_types=1);

namespace App\Pricing\Service;

use App\Pricing\DTO\PriceSelectionResultDTO;
use App\Pricing\DTO\PriceSelectionSnapshotDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\Exception\PriceReplayMismatchException;
use App\Pricing\ServiceInterface\PriceSelectionReplayServiceInterface;
use App\Pricing\ServiceInterface\PriceSelectionServiceInterface;

/**
 * Re-runs deterministic selection and verifies the captured provenance field by field.
 */
final class PriceSelectionReplayService implements PriceSelectionReplayServiceInterface
{
    public function __construct(private PriceSelectionServiceInterface $selectionService)
    {
    }

    /** Replays only the exact historical set revision identified by the snapshot. */
    public function replay(
        PriceSetDTO $historicalPriceSet,
        PriceSelectionSnapshotDTO $snapshot,
    ): PriceSelectionResultDTO {
        if ($historicalPriceSet->id !== $snapshot->priceSetId
            || $historicalPriceSet->revision !== $snapshot->priceSetRevision
            || $historicalPriceSet->priceableReference !== $snapshot->priceableReference
        ) {
            throw new PriceReplayMismatchException('Historical price set identity or revision does not match the snapshot.');
        }

        $result = $this->selectionService->select($historicalPriceSet, $snapshot->criteria());
        $selected = $result->selected;
        $list = $historicalPriceSet->priceList($selected->priceListId);

        $matches = $selected->id === $snapshot->priceId
            && $selected->revision === $snapshot->priceRevision
            && $selected->priceListId === $snapshot->priceListId
            && $list?->revision === $snapshot->priceListRevision
            && $selected->currencyCode === $snapshot->currencyCode
            && $selected->amountMinor === $snapshot->amountMinor
            && $selected->referenceAmountMinor === $snapshot->referenceAmountMinor
            && $selected->taxIncluded === $snapshot->taxIncluded;

        if (!$matches) {
            throw new PriceReplayMismatchException('Historical pricing material does not reproduce the captured selection.');
        }

        return $result;
    }
}
