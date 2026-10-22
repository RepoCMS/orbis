<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add slug column to identity_roles table
 */
final class Version20260825173933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add slug column to identity_roles table';
    }

    public function up(Schema $schema): void
    {
        // Add slug column to identity_roles
        $this->addSql('ALTER TABLE identity_roles ADD slug VARCHAR(60) NOT NULL DEFAULT ""');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5C656437989D9B62 ON identity_roles (slug)');

        // Populate slug from name for existing roles (lowercase, hyphenated)
        $this->addSql('UPDATE identity_roles SET slug = LOWER(REPLACE(name, " ", "-")) WHERE slug = ""');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_5C656437989D9B62 ON identity_roles');
        $this->addSql('ALTER TABLE identity_roles DROP slug');
    }
}
