<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit log table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('audit_log')) {
            return;
        }

        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, actor_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, action VARCHAR(20) NOT NULL, entity_type VARCHAR(120) NOT NULL, entity_id INT DEFAULT NULL, old_values LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', new_values LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL, INDEX IDX_AUDIT_OWNER_DATE (owner_id, created_at), INDEX IDX_AUDIT_ACTION_DATE (action, created_at), INDEX IDX_AUDIT_ACTOR (actor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_AUDIT_ACTOR FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_AUDIT_OWNER FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS audit_log');
    }
}