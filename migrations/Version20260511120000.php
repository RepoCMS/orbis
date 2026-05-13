<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega columna tracking_type a la tabla products.
 * Los productos existentes se marcan como 'serialized' por defecto.
 */
final class Version20260511120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tracking_type (serialized|non_serialized) to products table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE products ADD tracking_type VARCHAR(20) NOT NULL DEFAULT 'serialized'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP COLUMN tracking_type');
    }
}
