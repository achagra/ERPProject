<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create projet table for dashboard project management';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE projet (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, created_by_id INT NOT NULL, INDEX IDX_50159CA8B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA8B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA8B03A8386');
        $this->addSql('DROP TABLE projet');
    }
}