<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create entreprise table for company profile settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE entreprise (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, photo_filename VARCHAR(255) DEFAULT NULL, created_by_id INT NOT NULL, INDEX IDX_3B20FAD4B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_3B20FAD4B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_3B20FAD4B03A8386');
        $this->addSql('DROP TABLE entreprise');
    }
}