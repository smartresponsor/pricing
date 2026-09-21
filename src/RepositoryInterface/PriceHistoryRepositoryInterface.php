<?php

declare(strict_types=1);

namespace App\Pricing\RepositoryInterface;

use App\Pricing\Entity\PriceHistoryEntity;

/**
 * Persistence contract for append-only PriceSet revision history.
 */
interface PriceHistoryRepositoryInterface
{
    /** Finds one exact immutable PriceSet revision. */
    public function findRevision(string $priceSetId, int $revision): ?PriceHistoryEntity;

    /** Persists one new immutable revision. */
    public function save(PriceHistoryEntity $history): void;
}
