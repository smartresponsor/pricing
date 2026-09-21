<?php

declare(strict_types=1);

namespace App\Pricing\Service;

use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\Entity\PriceHistoryEntity;
use App\Pricing\Exception\PriceHistoryConflictException;
use App\Pricing\Exception\PriceHistoryNotFoundException;
use App\Pricing\RepositoryInterface\PriceHistoryRepositoryInterface;
use App\Pricing\ServiceInterface\PriceHistorySerializationServiceInterface;
use App\Pricing\ServiceInterface\PriceHistoryServiceInterface;

/**
 * Persists PriceSet revisions append-only and verifies payload integrity on retrieval.
 */
final class PriceHistoryService implements PriceHistoryServiceInterface
{
    public function __construct(
        private PriceHistoryRepositoryInterface $repository,
        private PriceHistorySerializationServiceInterface $codec,
    ) {
    }

    /** Records one immutable revision idempotently and rejects conflicting revision reuse. */
    public function record(PriceSetDTO $priceSet, ?\DateTimeImmutable $recordedAt = null): PriceHistoryEntity
    {
        $payload = $this->codec->encode($priceSet);
        $hash = $this->codec->hash($payload);
        $existing = $this->repository->findRevision($priceSet->id, $priceSet->revision);

        if (null !== $existing) {
            if ($existing->payloadHash() !== $hash) {
                throw new PriceHistoryConflictException(sprintf('PriceSet %s revision %d has already been recorded with different content.', $priceSet->id, $priceSet->revision));
            }

            return $existing;
        }

        $history = new PriceHistoryEntity(
            priceSetId: $priceSet->id,
            revision: $priceSet->revision,
            priceableReference: $priceSet->priceableReference,
            payload: $payload,
            payloadHash: $hash,
            recordedAt: $recordedAt ?? new \DateTimeImmutable(),
        );

        try {
            $this->repository->save($history);
        } catch (PriceHistoryConflictException $exception) {
            $concurrent = $this->repository->findRevision($priceSet->id, $priceSet->revision);
            if (null !== $concurrent && $concurrent->payloadHash() === $hash) {
                return $concurrent;
            }

            throw $exception;
        }

        return $history;
    }

    /** Loads and integrity-checks one exact historical PriceSet revision. */
    public function load(string $priceSetId, int $revision): PriceSetDTO
    {
        if ('' === trim($priceSetId) || $revision < 1) {
            throw new \InvalidArgumentException('Historical PriceSet id must not be empty and revision must be at least one.');
        }

        $history = $this->repository->findRevision($priceSetId, $revision);
        if (null === $history) {
            throw new PriceHistoryNotFoundException(sprintf('PriceSet %s revision %d was not found.', $priceSetId, $revision));
        }
        if ($this->codec->hash($history->payload()) !== $history->payloadHash()) {
            throw new PriceHistoryConflictException(sprintf('PriceSet %s revision %d failed history integrity verification.', $priceSetId, $revision));
        }

        return $this->codec->decode($history->payload());
    }
}
