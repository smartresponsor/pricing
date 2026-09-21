<?php

declare(strict_types=1);

namespace App\Pricing\Service;

use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSelectionResultDTO;
use App\Pricing\DTO\PriceSelectionSnapshotDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\ServiceInterface\PriceSelectionSnapshotServiceInterface;

/**
 * Converts a verified selection result into downstream-safe immutable provenance.
 */
final class PriceSelectionSnapshotService implements PriceSelectionSnapshotServiceInterface
{
    /** Captures the exact set/list/price revisions and monetary metadata that were selected. */
    public function snapshot(
        PriceSetDTO $priceSet,
        PriceSelectionCriteriaDTO $criteria,
        PriceSelectionResultDTO $result,
    ): PriceSelectionSnapshotDTO {
        $selected = $result->selected;
        if ($result->priceSetRevision !== $priceSet->revision
            || $selected->priceSetId !== $priceSet->id
            || $selected->priceableReference !== $priceSet->priceableReference
        ) {
            throw new \InvalidArgumentException('Selection result does not belong to the supplied price set revision.');
        }

        $list = $priceSet->priceList($selected->priceListId);

        return new PriceSelectionSnapshotDTO(
            priceSetId: $priceSet->id,
            priceSetRevision: $priceSet->revision,
            priceableReference: $priceSet->priceableReference,
            priceId: $selected->id,
            priceRevision: $selected->revision,
            priceListId: $selected->priceListId,
            priceListRevision: $list?->revision,
            currencyCode: $selected->currencyCode,
            quantity: $criteria->quantity,
            amountMinor: $selected->amountMinor,
            referenceAmountMinor: $selected->referenceAmountMinor,
            taxIncluded: $selected->taxIncluded,
            selectedAt: $criteria->at,
            context: $criteria->context,
            explanation: $result->explanation,
        );
    }
}
