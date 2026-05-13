<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Implementa ServiceZone (CLS) en Organization BC:
 *
 *   1. Crea tabla `service_zones`.
 *   2. Agrega columna `profile` a `employees` (TECHNICIAN | COORDINATOR | ADMINISTRATIVE).
 *   3. Agrega columna `service_zone_id` a `employees` (nullable — solo técnicos).
 *   4. Crea tabla `sites` (bug crítico: entidad existía sin persistencia).
 */
final class Version20260511000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ServiceZone BC: create service_zones table, add profile+service_zone_id to employees, create sites table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE service_zones (
                id          CHAR(26)     NOT NULL,
                name        VARCHAR(100) NOT NULL,
                status      VARCHAR(10)  NOT NULL DEFAULT 'active' COMMENT 'active | inactive',
                city_ids    JSON         NULL     COMMENT 'ULID[] referencias a Location BC',
                created_at  DATETIME     NULL,
                updated_at  DATETIME     NULL,
                deleted_at  DATETIME     NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX UNIQ_service_zones_name (name),
                INDEX idx_service_zones_status (status)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE employees
                ADD COLUMN profile         VARCHAR(20) NOT NULL DEFAULT 'administrative'
                    COMMENT 'technician | coordinator | administrative',
                ADD COLUMN service_zone_id CHAR(26)    NULL
                    COMMENT 'Solo técnicos (profile=technician) tienen zona asignada',
                ADD INDEX idx_employees_service_zone (service_zone_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE sites (
                id              CHAR(26)     NOT NULL,
                client_id       CHAR(26)     NOT NULL,
                city_id         CHAR(26)     NOT NULL COMMENT 'Referencia a Location BC',
                terminal_id     CHAR(26)     NOT NULL,
                name            VARCHAR(100) NOT NULL,
                status          VARCHAR(10)  NOT NULL DEFAULT 'active' COMMENT 'active | inactive',
                service_zone_id CHAR(26)     NULL     COMMENT 'Referencia a ServiceZone (Organization BC)',
                deleted_at      DATETIME     NULL,
                PRIMARY KEY (id),
                INDEX idx_sites_client_id       (client_id),
                INDEX idx_sites_service_zone_id (service_zone_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sites');
        $this->addSql('ALTER TABLE employees DROP COLUMN profile, DROP COLUMN service_zone_id, DROP INDEX idx_employees_service_zone');
        $this->addSql('DROP TABLE service_zones');
    }
}
