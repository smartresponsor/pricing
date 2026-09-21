<?php

declare(strict_types=1);

namespace App\Pricing\Repository;

use App\Pricing\Entity\PriceHistoryEntity;
use App\Pricing\Exception\PriceHistoryConflictException;
use App\Pricing\RepositoryInterface\PriceHistoryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for exact append-only PriceSet revision history.
 *
 * @extends ServiceEntityRepository<PriceHistoryEntity>
 */
final class PriceHistoryRepository extends ServiceEntityRepository implements PriceHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PriceHistoryEntity::class);
    }

    /** Finds one exact immutable PriceSet revision by business identity. */
    public function findRevision(string $priceSetId, int $revision): ?PriceHistoryEntity
    {
        return $this->findOneBy(['priceSetId' => $priceSetId, 'revision' => $revision]);
    }

    /** Persists a new revision and maps database uniqueness conflicts to Pricing semantics. */
    public function save(PriceHistoryEntity $history): void
    {
        try {
            $this->getEntityManager()->persist($history);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw new PriceHistoryConflictException(sprintf('PriceSet %s revision %d already exists.', $history->priceSetId(), $history->revision()), previous: $exception);
        }
    }
}
