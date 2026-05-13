<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506220152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status to nonserialized_stock; remove reserved_quantity; change unique key to (product_id, location_id, status)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uq_nonserialized_stock_product_location ON nonserialized_stock');
        $this->addSql('ALTER TABLE nonserialized_stock ADD status VARCHAR(20) NOT NULL, DROP reserved_quantity');
        $this->addSql('CREATE INDEX idx_nonserialized_stock_status ON nonserialized_stock (status)');
        $this->addSql('CREATE UNIQUE INDEX uq_nonserialized_stock_product_location_status ON nonserialized_stock (product_id, location_id, status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_nonserialized_stock_status ON nonserialized_stock');
        $this->addSql('DROP INDEX uq_nonserialized_stock_product_location_status ON nonserialized_stock');
        $this->addSql('ALTER TABLE nonserialized_stock ADD reserved_quantity INT NOT NULL, DROP status');
        $this->addSql('CREATE UNIQUE INDEX uq_nonserialized_stock_product_location ON nonserialized_stock (product_id, location_id)');
    }
}
