<?php

declare(strict_types=1);

namespace App\Pricing\ServiceInterface;

use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\Entity\PriceHistoryEntity;

/**
 * Owns append-only persistence and retrieval of immutable PriceSet revisions.
 */
interface PriceHistoryServiceInterface
{
    /** Records a revision idempotently, rejecting conflicting payload reuse. */
    public function record(PriceSetDTO $priceSet, ?\DateTimeImmutable $recordedAt = null): PriceHistoryEntity;

    /** Loads one exact historical PriceSet revision. */
    public function load(string $priceSetId, int $revision): PriceSetDTO;
}
