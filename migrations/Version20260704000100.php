<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260704000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create entrepot table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE entrepot (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, nom VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_26B0E5A3B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE entrepot ADD CONSTRAINT FK_26B0E5A3B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entrepot DROP FOREIGN KEY FK_26B0E5A3B03A8386');
        $this->addSql('DROP TABLE entrepot');
    }
}