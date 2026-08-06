<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Correcciones de esquema pendientes:
 *   1. Añade created_at / updated_at a la tabla `clients` (el ORM los mapea pero la columna no existía).
 *   2. Amplía la columna `status` de `employees` de VARCHAR(20) a VARCHAR(30) para soportar
 *      los nuevos valores `on_vacation` y `on_standby`.
 */
final class Version20260506163516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at/updated_at to clients; widen employees.status for new states';
    }

    public function up(Schema $schema): void
    {
        // Solo añade las columnas si aún no existen (puede que ya estén en la DB)
        $clientsTable = $schema->getTable('clients');
        if (!$clientsTable->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE clients ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD updated_at DATETIME DEFAULT NULL');
        }
        $this->addSql('ALTER TABLE employees MODIFY COLUMN status VARCHAR(30) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $clientsTable = $schema->getTable('clients');
        if ($clientsTable->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE clients DROP COLUMN created_at, DROP COLUMN updated_at');
        }
        $this->addSql('ALTER TABLE employees MODIFY COLUMN status VARCHAR(20) NOT NULL');
    }
}
