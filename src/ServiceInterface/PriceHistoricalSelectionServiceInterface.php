<?php

declare(strict_types=1);

namespace App\Pricing\ServiceInterface;

use App\Pricing\DTO\PriceSelectionResultDTO;
use App\Pricing\DTO\PriceSelectionSnapshotDTO;

/**
 * Replays a captured selection using Pricing-owned durable revision history.
 */
interface PriceHistoricalSelectionServiceInterface
{
    /** Loads the exact historical PriceSet revision and verifies deterministic replay. */
    public function replay(PriceSelectionSnapshotDTO $snapshot): PriceSelectionResultDTO;
}
