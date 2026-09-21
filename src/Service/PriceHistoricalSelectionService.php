<?php

declare(strict_types=1);

namespace App\Pricing\Service;

use App\Pricing\DTO\PriceSelectionResultDTO;
use App\Pricing\DTO\PriceSelectionSnapshotDTO;
use App\Pricing\ServiceInterface\PriceHistoricalSelectionServiceInterface;
use App\Pricing\ServiceInterface\PriceHistoryServiceInterface;
use App\Pricing\ServiceInterface\PriceSelectionReplayServiceInterface;

/**
 * Connects durable PriceSet revision history to deterministic selection replay.
 */
final class PriceHistoricalSelectionService implements PriceHistoricalSelectionServiceInterface
{
    public function __construct(
        private PriceHistoryServiceInterface $historyService,
        private PriceSelectionReplayServiceInterface $replayService,
    ) {
    }

    /** Loads the captured PriceSet revision and verifies the historical selection against it. */
    public function replay(PriceSelectionSnapshotDTO $snapshot): PriceSelectionResultDTO
    {
        $historicalPriceSet = $this->historyService->load($snapshot->priceSetId, $snapshot->priceSetRevision);

        return $this->replayService->replay($historicalPriceSet, $snapshot);
    }
}
