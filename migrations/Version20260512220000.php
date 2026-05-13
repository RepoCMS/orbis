<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create atm_components tree and ticket_finding_components pivot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE atm_components (
                id          VARCHAR(26)  NOT NULL,
                parent_id   VARCHAR(26)  NULL,
                name        VARCHAR(200) NOT NULL,
                aliases     VARCHAR(300) NULL COMMENT \'Nombres alternativos separados por coma: CDM, BRM, RCC\',
                level       ENUM(\'device\',\'module\',\'part\') NOT NULL DEFAULT \'device\',
                sort_order  SMALLINT     NOT NULL DEFAULT 0,
                active      TINYINT(1)   NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                INDEX idx_atm_components_parent (parent_id),
                INDEX idx_atm_components_level  (level),
                CONSTRAINT fk_atm_components_parent
                    FOREIGN KEY (parent_id) REFERENCES atm_components (id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $this->addSql('
            CREATE TABLE ticket_finding_components (
                id           VARCHAR(26)  NOT NULL,
                finding_id   VARCHAR(26)  NOT NULL,
                component_id VARCHAR(26)  NOT NULL,
                notes        VARCHAR(500) NULL,
                PRIMARY KEY (id),
                INDEX idx_tfc_finding   (finding_id),
                INDEX idx_tfc_component (component_id),
                CONSTRAINT fk_tfc_finding   FOREIGN KEY (finding_id)   REFERENCES ticket_findings  (id),
                CONSTRAINT fk_tfc_component FOREIGN KEY (component_id) REFERENCES atm_components   (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ticket_finding_components');
        $this->addSql('DROP TABLE atm_components');
    }
}
