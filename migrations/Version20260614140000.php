<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix enum values in Inventory: translate Spanish to English for NonserializedStockStatus and SerializedStockStatus';
    }

    public function up(Schema $schema): void
    {
        // ── NonserializedStockStatus ──────────────────────────────────────
        $this->addSql("
            UPDATE nonserialized_stock
            SET status = 'available'
            WHERE status = 'disponible'
        ");
        $this->addSql("
            UPDATE nonserialized_stock
            SET status = 'reserved'
            WHERE status = 'reservado'
        ");
        $this->addSql("
            UPDATE nonserialized_stock
            SET status = 'damaged'
            WHERE status = 'dañado'
        ");
        $this->addSql("
            UPDATE nonserialized_stock
            SET status = 'decommissioned'
            WHERE status = 'dado de baja'
        ");
        $this->addSql("
            UPDATE nonserialized_stock
            SET status = 'pending_review'
            WHERE status = 'pendiente de revisión'
        ");

        // ── SerializedStockStatus ─────────────────────────────────────────
        $this->addSql("
            UPDATE serialized_stock
            SET status = 'available'
            WHERE status = 'disponible'
        ");
        $this->addSql("
            UPDATE serialized_stock
            SET status = 'reserved'
            WHERE status = 'reservado'
        ");
        $this->addSql("
            UPDATE serialized_stock
            SET status = 'damaged'
            WHERE status = 'dañado'
        ");
        $this->addSql("
            UPDATE serialized_stock
            SET status = 'decommissioned'
            WHERE status = 'dado de baja'
        ");
        $this->addSql("
            UPDATE serialized_stock
            SET status = 'suspect'
            WHERE status = 'sospechoso'
        ");
    }

    public function down(Schema $schema): void
    {
        // ── Reverse NonserializedStockStatus ──────────────────────────────
        $this->addSql("
            UPDATE nonserialized_stock
            SET status = 'disponible'
            WHERE status = 'available'
        ");
        $this->addSql("
            UPDATE nonserialized_stock
            SET status = 'reservado'
            WHERE status = 'reserved'
        ");
        $this->addSql("
            UPDATE nonserialized_stock
            SET status = 'dañado'
            WHERE status = 'damaged'
        ");
        $this->addSql("
            UPDATE nonserialized_stock
            SET status = 'dado de baja'
            WHERE status = 'decommissioned'
        ");
        $this->addSql("
            UPDATE nonserialized_stock
            SET status = 'pendiente de revisión'
            WHERE status = 'pending_review'
        ");

        // ── Reverse SerializedStockStatus ─────────────────────────────────
        $this->addSql("
            UPDATE serialized_stock
            SET status = 'disponible'
            WHERE status = 'available'
        ");
        $this->addSql("
            UPDATE serialized_stock
            SET status = 'reservado'
            WHERE status = 'reserved'
        ");
        $this->addSql("
            UPDATE serialized_stock
            SET status = 'dañado'
            WHERE status = 'damaged'
        ");
        $this->addSql("
            UPDATE serialized_stock
            SET status = 'dado de baja'
            WHERE status = 'decommissioned'
        ");
        $this->addSql("
            UPDATE serialized_stock
            SET status = 'sospechoso'
            WHERE status = 'suspect'
        ");
    }
}
