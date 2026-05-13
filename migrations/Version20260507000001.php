<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename tables: catalogues → terminals, terminals → terminal_assignments
 * Column rename: terminal_assignments.catalogue_id → terminal_id
 */
final class Version20260507000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename Catalogue→Terminal and Terminal→TerminalAssignment tables/columns';
    }

    public function up(Schema $schema): void
    {
        // 1. Rename old `terminals` table (TerminalAssignment entity) to avoid conflict
        if ($schema->hasTable('terminals') && !$schema->hasTable('terminal_assignments')) {
            $this->addSql('DROP INDEX idx_terminals_catalogue_id ON terminals');
            $this->addSql('DROP INDEX idx_terminals_site_id ON terminals');
            $this->addSql('ALTER TABLE terminals RENAME TO terminal_assignments');
            $this->addSql('ALTER TABLE terminal_assignments RENAME COLUMN catalogue_id TO terminal_id');
            $this->addSql('CREATE INDEX idx_terminal_assignments_terminal_id ON terminal_assignments (terminal_id)');
            $this->addSql('CREATE INDEX idx_terminal_assignments_site_id ON terminal_assignments (site_id)');
        }

        // 2. Rename `catalogues` table (Terminal entity) to `terminals`
        if ($schema->hasTable('catalogues') && !$schema->hasTable('terminals')) {
            $this->addSql('DROP INDEX idx_catalogues_client_id ON catalogues');
            $this->addSql('ALTER TABLE catalogues RENAME TO terminals');
            $this->addSql('CREATE INDEX idx_terminals_client_id ON terminals (client_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // Reverse: terminals → catalogues
        if ($schema->hasTable('terminals') && !$schema->hasTable('catalogues')) {
            $this->addSql('DROP INDEX idx_terminals_client_id ON terminals');
            $this->addSql('ALTER TABLE terminals RENAME TO catalogues');
            $this->addSql('CREATE INDEX idx_catalogues_client_id ON catalogues (client_id)');
        }

        // Reverse: terminal_assignments → terminals
        if ($schema->hasTable('terminal_assignments') && !$schema->hasTable('terminals')) {
            $this->addSql('DROP INDEX idx_terminal_assignments_terminal_id ON terminal_assignments');
            $this->addSql('DROP INDEX idx_terminal_assignments_site_id ON terminal_assignments');
            $this->addSql('ALTER TABLE terminal_assignments RENAME COLUMN terminal_id TO catalogue_id');
            $this->addSql('ALTER TABLE terminal_assignments RENAME TO terminals');
            $this->addSql('CREATE INDEX idx_terminals_catalogue_id ON terminals (catalogue_id)');
            $this->addSql('CREATE INDEX idx_terminals_site_id ON terminals (site_id)');
        }
    }
}
