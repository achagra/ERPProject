<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create client and fournisseur tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, type VARCHAR(30) NOT NULL, nom_complet VARCHAR(255) NOT NULL, civilite VARCHAR(10) NOT NULL, nom_entreprise VARCHAR(255) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, numero_fiscale VARCHAR(100) DEFAULT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(30) DEFAULT NULL, pays VARCHAR(120) NOT NULL, adresse VARCHAR(255) NOT NULL, region VARCHAR(120) NOT NULL, INDEX IDX_C7440455B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fournisseur (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, type VARCHAR(30) NOT NULL, nom_complet VARCHAR(255) NOT NULL, civilite VARCHAR(10) NOT NULL, nom_entreprise VARCHAR(255) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, numero_fiscale VARCHAR(100) DEFAULT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(30) DEFAULT NULL, pays VARCHAR(120) NOT NULL, adresse VARCHAR(255) NOT NULL, region VARCHAR(120) NOT NULL, INDEX IDX_DA8C43BEB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE fournisseur ADD CONSTRAINT FK_DA8C43BEB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455B03A8386');
        $this->addSql('ALTER TABLE fournisseur DROP FOREIGN KEY FK_DA8C43BEB03A8386');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE fournisseur');
    }
}
