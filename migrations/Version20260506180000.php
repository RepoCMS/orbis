<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crea la tabla employee_special_periods para registrar períodos especiales
 * de los empleados con ciclo de vida completo:
 *
 * - VACATION : el empleado está ausente; el estado se cambia automáticamente
 *              via comando de consola org:process-special-periods
 * - STANDBY  : el empleado trabaja en fin de semana / día festivo (pago extra);
 *              no modifica el estado del empleado.
 *
 * Columna status: scheduled | in_progress | completed | cancelled
 * ended_at es NOT NULL: la fecha fin siempre se programa al crear el período.
 */
final class Version20260506180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create employee_special_periods table (scheduled periods with status)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE employee_special_periods (
                id          CHAR(26)     NOT NULL,
                employee_id CHAR(26)     NOT NULL,
                type        VARCHAR(10)  NOT NULL COMMENT 'vacation | standby',
                status      VARCHAR(12)  NOT NULL DEFAULT 'scheduled' COMMENT 'scheduled | in_progress | completed | cancelled',
                started_at  DATETIME     NOT NULL,
                ended_at    DATETIME     NOT NULL,
                notes       VARCHAR(500) NULL,
                PRIMARY KEY (id),
                INDEX idx_esp_employee_id (employee_id),
                INDEX idx_esp_status      (status),
                INDEX idx_esp_started_at  (started_at),
                INDEX idx_esp_ended_at    (ended_at)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE employee_special_periods');
    }
}
