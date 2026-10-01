<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260711093633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_achat DROP FOREIGN KEY `FK_2D0A9A6D16B6F75B`');
        $this->addSql('ALTER TABLE commande_achat DROP FOREIGN KEY `FK_2D0A9A6D2C1DFE4`');
        $this->addSql('ALTER TABLE commande_achat DROP FOREIGN KEY `FK_2D0A9A6DB03A8386`');
        $this->addSql('ALTER TABLE commande_achat CHANGE date_facture date_facture DATE NOT NULL');
        $this->addSql('DROP INDEX idx_2d0a9a6d2c1dfe4 ON commande_achat');
        $this->addSql('CREATE INDEX IDX_1FC15B95670C757F ON commande_achat (fournisseur_id)');
        $this->addSql('DROP INDEX idx_2d0a9a6d16b6f75b ON commande_achat');
        $this->addSql('CREATE INDEX IDX_1FC15B9572831E97 ON commande_achat (entrepot_id)');
        $this->addSql('DROP INDEX idx_2d0a9a6db03a8386 ON commande_achat');
        $this->addSql('CREATE INDEX IDX_1FC15B95B03A8386 ON commande_achat (created_by_id)');
        $this->addSql('ALTER TABLE commande_achat ADD CONSTRAINT `FK_2D0A9A6D16B6F75B` FOREIGN KEY (entrepot_id) REFERENCES entrepot (id)');
        $this->addSql('ALTER TABLE commande_achat ADD CONSTRAINT `FK_2D0A9A6D2C1DFE4` FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE commande_achat ADD CONSTRAINT `FK_2D0A9A6DB03A8386` FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE commande_vente DROP FOREIGN KEY `FK_887E6A7516B6F75B`');
        $this->addSql('ALTER TABLE commande_vente DROP FOREIGN KEY `FK_887E6A75B03A8386`');
        $this->addSql('ALTER TABLE commande_vente DROP FOREIGN KEY `FK_887E6A75C01AD4D1`');
        $this->addSql('ALTER TABLE commande_vente CHANGE date_commande date_commande DATE NOT NULL');
        $this->addSql('DROP INDEX idx_887e6a75c01ad4d1 ON commande_vente');
        $this->addSql('CREATE INDEX IDX_B1E2F58F19EB6921 ON commande_vente (client_id)');
        $this->addSql('DROP INDEX idx_887e6a7516b6f75b ON commande_vente');
        $this->addSql('CREATE INDEX IDX_B1E2F58F72831E97 ON commande_vente (entrepot_id)');
        $this->addSql('DROP INDEX idx_887e6a75b03a8386 ON commande_vente');
        $this->addSql('CREATE INDEX IDX_B1E2F58FB03A8386 ON commande_vente (created_by_id)');
        $this->addSql('ALTER TABLE commande_vente ADD CONSTRAINT `FK_887E6A7516B6F75B` FOREIGN KEY (entrepot_id) REFERENCES entrepot (id)');
        $this->addSql('ALTER TABLE commande_vente ADD CONSTRAINT `FK_887E6A75B03A8386` FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE commande_vente ADD CONSTRAINT `FK_887E6A75C01AD4D1` FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE entrepot DROP FOREIGN KEY `FK_26B0E5A3B03A8386`');
        $this->addSql('DROP INDEX idx_26b0e5a3b03a8386 ON entrepot');
        $this->addSql('CREATE INDEX IDX_D805175AB03A8386 ON entrepot (created_by_id)');
        $this->addSql('ALTER TABLE entrepot ADD CONSTRAINT `FK_26B0E5A3B03A8386` FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY `FK_3B20FAD4B03A8386`');
        $this->addSql('DROP INDEX idx_3b20fad4b03a8386 ON entreprise');
        $this->addSql('CREATE INDEX IDX_D19FA60B03A8386 ON entreprise (created_by_id)');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT `FK_3B20FAD4B03A8386` FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE fournisseur DROP FOREIGN KEY `FK_DA8C43BEB03A8386`');
        $this->addSql('DROP INDEX idx_da8c43beb03a8386 ON fournisseur');
        $this->addSql('CREATE INDEX IDX_369ECA32B03A8386 ON fournisseur (created_by_id)');
        $this->addSql('ALTER TABLE fournisseur ADD CONSTRAINT `FK_DA8C43BEB03A8386` FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY `FK_50159CA8B03A8386`');
        $this->addSql('DROP INDEX idx_50159ca8b03a8386 ON projet');
        $this->addSql('CREATE INDEX IDX_50159CA9B03A8386 ON projet (created_by_id)');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT `FK_50159CA8B03A8386` FOREIGN KEY (created_by_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_achat DROP FOREIGN KEY FK_1FC15B95670C757F');
        $this->addSql('ALTER TABLE commande_achat DROP FOREIGN KEY FK_1FC15B9572831E97');
        $this->addSql('ALTER TABLE commande_achat DROP FOREIGN KEY FK_1FC15B95B03A8386');
        $this->addSql('ALTER TABLE commande_achat CHANGE date_facture date_facture DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('DROP INDEX idx_1fc15b95b03a8386 ON commande_achat');
        $this->addSql('CREATE INDEX IDX_2D0A9A6DB03A8386 ON commande_achat (created_by_id)');
        $this->addSql('DROP INDEX idx_1fc15b95670c757f ON commande_achat');
        $this->addSql('CREATE INDEX IDX_2D0A9A6D2C1DFE4 ON commande_achat (fournisseur_id)');
        $this->addSql('DROP INDEX idx_1fc15b9572831e97 ON commande_achat');
        $this->addSql('CREATE INDEX IDX_2D0A9A6D16B6F75B ON commande_achat (entrepot_id)');
        $this->addSql('ALTER TABLE commande_achat ADD CONSTRAINT FK_1FC15B95670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE commande_achat ADD CONSTRAINT FK_1FC15B9572831E97 FOREIGN KEY (entrepot_id) REFERENCES entrepot (id)');
        $this->addSql('ALTER TABLE commande_achat ADD CONSTRAINT FK_1FC15B95B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE commande_vente DROP FOREIGN KEY FK_B1E2F58F19EB6921');
        $this->addSql('ALTER TABLE commande_vente DROP FOREIGN KEY FK_B1E2F58F72831E97');
        $this->addSql('ALTER TABLE commande_vente DROP FOREIGN KEY FK_B1E2F58FB03A8386');
        $this->addSql('ALTER TABLE commande_vente CHANGE date_commande date_commande DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('DROP INDEX idx_b1e2f58fb03a8386 ON commande_vente');
        $this->addSql('CREATE INDEX IDX_887E6A75B03A8386 ON commande_vente (created_by_id)');
        $this->addSql('DROP INDEX idx_b1e2f58f19eb6921 ON commande_vente');
        $this->addSql('CREATE INDEX IDX_887E6A75C01AD4D1 ON commande_vente (client_id)');
        $this->addSql('DROP INDEX idx_b1e2f58f72831e97 ON commande_vente');
        $this->addSql('CREATE INDEX IDX_887E6A7516B6F75B ON commande_vente (entrepot_id)');
        $this->addSql('ALTER TABLE commande_vente ADD CONSTRAINT FK_B1E2F58F19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE commande_vente ADD CONSTRAINT FK_B1E2F58F72831E97 FOREIGN KEY (entrepot_id) REFERENCES entrepot (id)');
        $this->addSql('ALTER TABLE commande_vente ADD CONSTRAINT FK_B1E2F58FB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE entrepot DROP FOREIGN KEY FK_D805175AB03A8386');
        $this->addSql('DROP INDEX idx_d805175ab03a8386 ON entrepot');
        $this->addSql('CREATE INDEX IDX_26B0E5A3B03A8386 ON entrepot (created_by_id)');
        $this->addSql('ALTER TABLE entrepot ADD CONSTRAINT FK_D805175AB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA60B03A8386');
        $this->addSql('DROP INDEX idx_d19fa60b03a8386 ON entreprise');
        $this->addSql('CREATE INDEX IDX_3B20FAD4B03A8386 ON entreprise (created_by_id)');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA60B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE fournisseur DROP FOREIGN KEY FK_369ECA32B03A8386');
        $this->addSql('DROP INDEX idx_369eca32b03a8386 ON fournisseur');
        $this->addSql('CREATE INDEX IDX_DA8C43BEB03A8386 ON fournisseur (created_by_id)');
        $this->addSql('ALTER TABLE fournisseur ADD CONSTRAINT FK_369ECA32B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA9B03A8386');
        $this->addSql('DROP INDEX idx_50159ca9b03a8386 ON projet');
        $this->addSql('CREATE INDEX IDX_50159CA8B03A8386 ON projet (created_by_id)');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA9B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
    }
}
