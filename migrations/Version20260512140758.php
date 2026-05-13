<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512140758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE products CHANGE tracking_type tracking_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE regions RENAME INDEX idx_regions_name TO UNIQ_A26779F35E237E06');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE products CHANGE tracking_type tracking_type VARCHAR(20) DEFAULT \'serialized\' NOT NULL');
        $this->addSql('ALTER TABLE regions RENAME INDEX uniq_a26779f35e237e06 TO idx_regions_name');
    }
}
