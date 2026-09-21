<?php

declare(strict_types=1);

use App\Pricing\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

require dirname(__DIR__).'/vendor/autoload.php';

$kernel = new Kernel('test', false);
$kernel->boot();

try {
    $container = $kernel->getContainer();
    if (!$container->has('doctrine.orm.entity_manager')) {
        fwrite(STDERR, "Doctrine entity manager service is unavailable.\n");
        exit(2);
    }

    $entityManager = $container->get('doctrine.orm.entity_manager');
    if (!$entityManager instanceof EntityManagerInterface) {
        fwrite(STDERR, "Doctrine entity manager service has an unexpected type.\n");
        exit(2);
    }

    $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
    $sql = (new SchemaTool($entityManager))->getUpdateSchemaSql($metadata);
    $sql = array_values(array_filter(
        $sql,
        static fn (string $statement): bool => 1 !== preg_match('/^DROP TABLE ["`]?doctrine_migration_versions["`]?$/i', trim($statement)),
    ));

    if ([] !== $sql) {
        fwrite(STDERR, 'Doctrine connection: '.json_encode($entityManager->getConnection()->getParams(), JSON_UNESCAPED_SLASHES)."\n");
        fwrite(STDERR, "Doctrine schema differs from current ORM metadata:\n");
        foreach ($sql as $statement) {
            fwrite(STDERR, '- '.$statement."\n");
        }
        exit(1);
    }

    fwrite(STDOUT, "Doctrine schema matches current ORM metadata.\n");
    exit(0);
} finally {
    $kernel->shutdown();
}
