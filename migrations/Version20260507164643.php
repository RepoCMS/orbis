<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507164643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stock_requests (reference VARCHAR(30) NOT NULL, requested_by_id VARCHAR(26) NOT NULL, product_id VARCHAR(26) NOT NULL, serialized_stock_id VARCHAR(26) DEFAULT NULL, quantity INT NOT NULL, source_location_id VARCHAR(26) NOT NULL, destination_location_id VARCHAR(26) NOT NULL, status VARCHAR(20) NOT NULL, notes LONGTEXT DEFAULT NULL, approved_by_id VARCHAR(26) DEFAULT NULL, approved_at DATETIME DEFAULT NULL, rejected_by_id VARCHAR(26) DEFAULT NULL, rejected_at DATETIME DEFAULT NULL, rejection_reason LONGTEXT DEFAULT NULL, fulfilled_movement_id VARCHAR(26) DEFAULT NULL, cancelled_by_id VARCHAR(26) DEFAULT NULL, created_at DATETIME NOT NULL, id VARCHAR(26) NOT NULL, UNIQUE INDEX UNIQ_34438213AEA34913 (reference), INDEX idx_stock_requests_status (status), INDEX idx_stock_requests_requested_by (requested_by_id), INDEX idx_stock_requests_product_id (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE stock_requests');
    }
}
