<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product asset type and description fields';
    }

    public function up(Schema $schema): void
    {
        $product = $schema->getTable('product');

        if (!$product->hasColumn('asset_type')) {
            $this->addSql('ALTER TABLE product ADD asset_type VARCHAR(50) DEFAULT NULL');
        }

        if (!$product->hasColumn('description')) {
            $this->addSql('ALTER TABLE product ADD description LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $product = $schema->getTable('product');

        if ($product->hasColumn('asset_type')) {
            $this->addSql('ALTER TABLE product DROP asset_type');
        }

        if ($product->hasColumn('description')) {
            $this->addSql('ALTER TABLE product DROP description');
        }
    }
}