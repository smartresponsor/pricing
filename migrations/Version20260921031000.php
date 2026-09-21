<?php

declare(strict_types=1);

namespace App\Pricing\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates append-only durable storage for immutable PriceSet revision snapshots.
 */
final class Version20260921031000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create pricing_price_history append-only PriceSet revision storage.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('pricing_price_history');
        $table->addColumn('id', 'integer')->setAutoincrement(true);
        $table->addColumn('price_set_id', 'string', ['length' => 160]);
        $table->addColumn('revision', 'integer');
        $table->addColumn('priceable_reference', 'string', ['length' => 255]);
        $table->addColumn('payload', 'json');
        $table->addColumn('payload_hash', 'string', ['length' => 64]);
        $table->addColumn('recorded_at', 'datetime_immutable');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['price_set_id', 'revision'], 'uniq_pricing_price_history_set_revision');
        $table->addIndex(['priceable_reference'], 'idx_pricing_price_history_reference');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('pricing_price_history');
    }
}
