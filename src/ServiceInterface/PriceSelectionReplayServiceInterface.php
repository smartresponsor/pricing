<?php

declare(strict_types=1);

namespace App\Pricing\ServiceInterface;

use App\Pricing\DTO\PriceSelectionResultDTO;
use App\Pricing\DTO\PriceSelectionSnapshotDTO;
use App\Pricing\DTO\PriceSetDTO;

/**
 * Replays a captured decision against the exact historical PriceSet revision.
 */
interface PriceSelectionReplayServiceInterface
{
    /** Returns the reproduced selection or throws when historical material has drifted. */
    public function replay(
        PriceSetDTO $historicalPriceSet,
        PriceSelectionSnapshotDTO $snapshot,
    ): PriceSelectionResultDTO;
}
