<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Integration;

use App\Pricing\DTO\PriceDefinitionDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\Entity\PriceHistoryEntity;
use App\Pricing\Exception\PriceHistoryConflictException;
use App\Pricing\Repository\PriceHistoryRepository;
use App\Pricing\Service\PriceHistorySerializationService;
use App\Pricing\Service\PriceHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Verifies durable history through the real standalone Doctrine container and SQLite constraint layer. */
final class PriceHistoryPersistenceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $tool = new SchemaTool($this->entityManager);
        $metadata = [$this->entityManager->getClassMetadata(PriceHistoryEntity::class)];
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->isOpen()) {
            $tool = new SchemaTool($this->entityManager);
            $tool->dropSchema([$this->entityManager->getClassMetadata(PriceHistoryEntity::class)]);
        }

        parent::tearDown();
    }

    /** Proves a PriceSet revision survives an actual Doctrine persist/load cycle. */
    public function testHistoryPersistsAndLoadsThroughDoctrine(): void
    {
        $repository = $this->entityManager->getRepository(PriceHistoryEntity::class);
        self::assertInstanceOf(PriceHistoryRepository::class, $repository);
        $service = new PriceHistoryService($repository, new PriceHistorySerializationService());
        $set = new PriceSetDTO(
            'persisted-set',
            'catalog:variant:1001',
            [new PriceDefinitionDTO('price', 'persisted-set', 'catalog:variant:1001', 'USD', 1999, revision: 2)],
            revision: 5,
        );

        $stored = $service->record($set, new \DateTimeImmutable('2026-09-20T22:00:00+00:00'));
        $this->entityManager->clear();
        $loaded = $service->load('persisted-set', 5);

        self::assertNotNull($stored->id());
        self::assertSame(5, $loaded->revision);
        self::assertSame(1999, $loaded->prices[0]->amountMinor);
    }

    /** Proves the database unique constraint rejects concurrent reuse of one PriceSet revision. */
    public function testUniqueRevisionConstraintRejectsConflictingInsert(): void
    {
        $repository = $this->entityManager->getRepository(PriceHistoryEntity::class);
        self::assertInstanceOf(PriceHistoryRepository::class, $repository);
        $codec = new PriceHistorySerializationService();
        $set = new PriceSetDTO(
            'concurrent-set',
            'catalog:variant:1002',
            [new PriceDefinitionDTO('price', 'concurrent-set', 'catalog:variant:1002', 'USD', 1000)],
            revision: 3,
        );
        $payload = $codec->encode($set);
        $first = new PriceHistoryEntity(
            'concurrent-set',
            3,
            'catalog:variant:1002',
            $payload,
            $codec->hash($payload),
            new \DateTimeImmutable('2026-09-20T22:00:00+00:00'),
        );
        $second = new PriceHistoryEntity(
            'concurrent-set',
            3,
            'catalog:variant:1002',
            $payload,
            $codec->hash($payload),
            new \DateTimeImmutable('2026-09-20T22:00:01+00:00'),
        );

        $repository->save($first);

        $this->expectException(PriceHistoryConflictException::class);
        $repository->save($second);
    }
}
