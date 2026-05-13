<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512143310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stock_minimums and serial_history tables for Inventory BC';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE serial_history (serialized_stock_id VARCHAR(26) NOT NULL, previous_serial_number VARCHAR(100) NOT NULL, new_serial_number VARCHAR(100) NOT NULL, reason VARCHAR(20) NOT NULL, changed_at DATETIME NOT NULL, id VARCHAR(26) NOT NULL, INDEX idx_serial_history_serialized_stock_id (serialized_stock_id), INDEX idx_serial_history_changed_at (changed_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_minimums (product_id VARCHAR(26) NOT NULL, location_id VARCHAR(26) NOT NULL, ideal_quantity INT NOT NULL, alert_threshold INT NOT NULL, id VARCHAR(26) NOT NULL, INDEX idx_stock_minimums_location_id (location_id), INDEX idx_stock_minimums_product_id (product_id), UNIQUE INDEX uq_stock_minimums_product_location (product_id, location_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE serial_history');
        $this->addSql('DROP TABLE stock_minimums');
    }
}
