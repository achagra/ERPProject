<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260704000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create commande vente and commande achat tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE commande_vente (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, client_id INT NOT NULL, entrepot_id INT NOT NULL, date_commande DATE NOT NULL COMMENT "(DC2Type:date_immutable)", notes LONGTEXT DEFAULT NULL, INDEX IDX_887E6A75B03A8386 (created_by_id), INDEX IDX_887E6A75C01AD4D1 (client_id), INDEX IDX_887E6A7516B6F75B (entrepot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commande_achat (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, fournisseur_id INT NOT NULL, entrepot_id INT NOT NULL, date_facture DATE NOT NULL COMMENT "(DC2Type:date_immutable)", notes LONGTEXT DEFAULT NULL, INDEX IDX_2D0A9A6DB03A8386 (created_by_id), INDEX IDX_2D0A9A6D2C1DFE4 (fournisseur_id), INDEX IDX_2D0A9A6D16B6F75B (entrepot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande_vente ADD CONSTRAINT FK_887E6A75B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE commande_vente ADD CONSTRAINT FK_887E6A75C01AD4D1 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE commande_vente ADD CONSTRAINT FK_887E6A7516B6F75B FOREIGN KEY (entrepot_id) REFERENCES entrepot (id)');
        $this->addSql('ALTER TABLE commande_achat ADD CONSTRAINT FK_2D0A9A6DB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE commande_achat ADD CONSTRAINT FK_2D0A9A6D2C1DFE4 FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE commande_achat ADD CONSTRAINT FK_2D0A9A6D16B6F75B FOREIGN KEY (entrepot_id) REFERENCES entrepot (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande_vente DROP FOREIGN KEY FK_887E6A75B03A8386');
        $this->addSql('ALTER TABLE commande_vente DROP FOREIGN KEY FK_887E6A75C01AD4D1');
        $this->addSql('ALTER TABLE commande_vente DROP FOREIGN KEY FK_887E6A7516B6F75B');
        $this->addSql('ALTER TABLE commande_achat DROP FOREIGN KEY FK_2D0A9A6DB03A8386');
        $this->addSql('ALTER TABLE commande_achat DROP FOREIGN KEY FK_2D0A9A6D2C1DFE4');
        $this->addSql('ALTER TABLE commande_achat DROP FOREIGN KEY FK_2D0A9A6D16B6F75B');
        $this->addSql('DROP TABLE commande_vente');
        $this->addSql('DROP TABLE commande_achat');
    }
}