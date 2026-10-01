<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723112612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add client and fournisseur relations to product';
    }

    public function up(Schema $schema): void
    {
        $product = $schema->getTable('product');

        if (!$product->hasColumn('client_id')) {
            $this->addSql('ALTER TABLE product ADD client_id INT DEFAULT NULL');
        }

        if (!$product->hasColumn('fournisseur_id')) {
            $this->addSql('ALTER TABLE product ADD fournisseur_id INT DEFAULT NULL');
        }

        if (!$product->hasIndex('IDX_D34A04AD19EB6921')) {
            $this->addSql('CREATE INDEX IDX_D34A04AD19EB6921 ON product (client_id)');
        }

        if (!$product->hasIndex('IDX_D34A04AD670C757F')) {
            $this->addSql('CREATE INDEX IDX_D34A04AD670C757F ON product (fournisseur_id)');
        }

        if (!$product->hasForeignKey('FK_D34A04AD19EB6921')) {
            $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        }

        if (!$product->hasForeignKey('FK_D34A04AD670C757F')) {
            $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        }
    }

    public function down(Schema $schema): void
    {
        $product = $schema->getTable('product');

        if ($product->hasForeignKey('FK_D34A04AD19EB6921')) {
            $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD19EB6921');
        }

        if ($product->hasForeignKey('FK_D34A04AD670C757F')) {
            $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD670C757F');
        }

        if ($product->hasIndex('IDX_D34A04AD19EB6921')) {
            $this->addSql('DROP INDEX IDX_D34A04AD19EB6921 ON product');
        }

        if ($product->hasIndex('IDX_D34A04AD670C757F')) {
            $this->addSql('DROP INDEX IDX_D34A04AD670C757F ON product');
        }

        if ($product->hasColumn('client_id')) {
            $this->addSql('ALTER TABLE product DROP client_id');
        }

        if ($product->hasColumn('fournisseur_id')) {
            $this->addSql('ALTER TABLE product DROP fournisseur_id');
        }
    }
}
