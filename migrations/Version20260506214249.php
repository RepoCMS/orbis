<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506214249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE inventory_entries (reference VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, notes LONGTEXT DEFAULT NULL, created_by VARCHAR(26) NOT NULL, created_at DATETIME NOT NULL, confirmed_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, INDEX idx_inventory_entries_status (status), INDEX idx_inventory_entries_created_by (created_by), UNIQUE INDEX uq_inventory_entries_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE inventory_entry_items (entry_id VARCHAR(26) NOT NULL, product_id VARCHAR(26) NOT NULL, location_id VARCHAR(26) NOT NULL, purchase_order_item_id VARCHAR(26) DEFAULT NULL, serial_number VARCHAR(100) DEFAULT NULL, quantity INT NOT NULL, id VARCHAR(26) NOT NULL, INDEX idx_inventory_entry_items_entry_id (entry_id), INDEX idx_inventory_entry_items_product_id (product_id), INDEX idx_inventory_entry_items_purchase_order_item_id (purchase_order_item_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE purchase_order_items (purchase_order_id VARCHAR(26) NOT NULL, product_id VARCHAR(26) NOT NULL, quantity_ordered INT NOT NULL, quantity_received INT NOT NULL, id VARCHAR(26) NOT NULL, INDEX idx_purchase_order_items_purchase_order_id (purchase_order_id), INDEX idx_purchase_order_items_product_id (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE purchase_orders (reference VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, notes LONGTEXT DEFAULT NULL, created_by VARCHAR(26) NOT NULL, created_at DATETIME NOT NULL, id VARCHAR(26) NOT NULL, INDEX idx_purchase_orders_status (status), INDEX idx_purchase_orders_created_by (created_by), UNIQUE INDEX uq_purchase_orders_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE repairs (serialized_stock_id VARCHAR(26) NOT NULL, laboratory_id VARCHAR(26) NOT NULL, assigned_by_id VARCHAR(26) NOT NULL, technician_id VARCHAR(26) DEFAULT NULL, status VARCHAR(20) NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, INDEX idx_repairs_serialized_stock_id (serialized_stock_id), INDEX idx_repairs_laboratory_id (laboratory_id), INDEX idx_repairs_status (status), INDEX idx_repairs_assigned_by_id (assigned_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_transformations (serialized_stock_id VARCHAR(26) NOT NULL, laboratory_id VARCHAR(26) NOT NULL, performed_by_id VARCHAR(26) NOT NULL, original_product_id VARCHAR(26) NOT NULL, new_product_id VARCHAR(26) DEFAULT NULL, original_serial_number VARCHAR(100) DEFAULT NULL, new_serial_number VARCHAR(100) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, id VARCHAR(26) NOT NULL, INDEX idx_stock_transformations_serialized_stock_id (serialized_stock_id), INDEX idx_stock_transformations_laboratory_id (laboratory_id), INDEX idx_stock_transformations_performed_by_id (performed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE serialized_stock CHANGE location_id location_id VARCHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE stock_locations ADD type VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE inventory_entries');
        $this->addSql('DROP TABLE inventory_entry_items');
        $this->addSql('DROP TABLE purchase_order_items');
        $this->addSql('DROP TABLE purchase_orders');
        $this->addSql('DROP TABLE repairs');
        $this->addSql('DROP TABLE stock_transformations');
        $this->addSql('ALTER TABLE serialized_stock CHANGE location_id location_id VARCHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE stock_locations DROP type');
    }
}
