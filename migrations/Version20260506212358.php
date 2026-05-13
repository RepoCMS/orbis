<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506212358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nonserialized_stock (product_id VARCHAR(26) NOT NULL, location_id VARCHAR(26) NOT NULL, quantity INT NOT NULL, reserved_quantity INT NOT NULL, deleted_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, INDEX idx_nonserialized_stock_product_id (product_id), INDEX idx_nonserialized_stock_location_id (location_id), UNIQUE INDEX uq_nonserialized_stock_product_location (product_id, location_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE products (code VARCHAR(50) NOT NULL, name VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, deleted_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, INDEX idx_products_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE serialized_stock (product_id VARCHAR(26) NOT NULL, serial_number VARCHAR(100) NOT NULL, location_id VARCHAR(26) DEFAULT NULL, status VARCHAR(20) NOT NULL, deleted_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, INDEX idx_serialized_stock_product_id (product_id), INDEX idx_serialized_stock_location_id (location_id), INDEX idx_serialized_stock_status (status), UNIQUE INDEX uq_serialized_stock_product_serial (product_id, serial_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_locations (name VARCHAR(100) NOT NULL, city VARCHAR(100) NOT NULL, address VARCHAR(255) NOT NULL, deleted_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, INDEX idx_stock_locations_city (city), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_movements (product_id VARCHAR(26) NOT NULL, type VARCHAR(20) NOT NULL, source_location_id VARCHAR(26) DEFAULT NULL, destination_location_id VARCHAR(26) DEFAULT NULL, serialized_stock_id VARCHAR(26) DEFAULT NULL, quantity INT NOT NULL, status VARCHAR(20) NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, INDEX idx_stock_movements_product_id (product_id), INDEX idx_stock_movements_status (status), INDEX idx_stock_movements_source_location_id (source_location_id), INDEX idx_stock_movements_destination_location_id (destination_location_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE nonserialized_stock');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE serialized_stock');
        $this->addSql('DROP TABLE stock_locations');
        $this->addSql('DROP TABLE stock_movements');
    }
}
