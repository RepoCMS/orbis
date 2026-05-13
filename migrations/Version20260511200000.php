<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Support BC — creates 9 tables for ticket management lifecycle.
 */
final class Version20260511200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Support BC: tickets, ticket_assignments, ticket_journeys, ticket_findings, ticket_spare_parts, ticket_pending_spare_parts, ticket_evidences, ticket_pauses, ticket_reschedules';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE tickets (
                id                     CHAR(26)     NOT NULL,
                ticket_number          VARCHAR(20)  NOT NULL,
                client_id              CHAR(26)     NOT NULL,
                terminal_assignment_id CHAR(26)     NOT NULL,
                service_type           VARCHAR(50)  NOT NULL,
                description            LONGTEXT     NOT NULL,
                status                 VARCHAR(30)  NOT NULL,
                created_by             CHAR(26)     NOT NULL,
                scheduled_at           DATETIME     NULL COMMENT '(DC2Type:datetime_immutable)',
                sla_deadline_at        DATETIME     NULL COMMENT '(DC2Type:datetime_immutable)',
                in_transit_out_at      DATETIME     NULL COMMENT '(DC2Type:datetime_immutable)',
                waiting_start_at       DATETIME     NULL COMMENT '(DC2Type:datetime_immutable)',
                assistance_start_at    DATETIME     NULL COMMENT '(DC2Type:datetime_immutable)',
                in_transit_back_at     DATETIME     NULL COMMENT '(DC2Type:datetime_immutable)',
                closed_at              DATETIME     NULL COMMENT '(DC2Type:datetime_immutable)',
                cancelled_at           DATETIME     NULL COMMENT '(DC2Type:datetime_immutable)',
                cancellation_reason    LONGTEXT     NULL,
                deleted_at             DATETIME     NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id),
                UNIQUE INDEX uq_tickets_number (ticket_number),
                INDEX idx_tickets_client_id (client_id),
                INDEX idx_tickets_terminal_assignment_id (terminal_assignment_id),
                INDEX idx_tickets_status (status),
                INDEX idx_tickets_created_by (created_by),
                INDEX idx_tickets_scheduled_at (scheduled_at),
                INDEX idx_tickets_deleted_at (deleted_at)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE ticket_assignments (
                id                CHAR(26) NOT NULL,
                ticket_id         CHAR(26) NOT NULL,
                employee_id       CHAR(26) NOT NULL,
                is_in_charge      TINYINT(1) NOT NULL DEFAULT 0,
                assigned_at       DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                reassigned_from   CHAR(26) NULL,
                PRIMARY KEY(id),
                INDEX idx_ticket_assignments_ticket_id   (ticket_id),
                INDEX idx_ticket_assignments_employee_id (employee_id),
                CONSTRAINT fk_ticket_assignments_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE ticket_journeys (
                id               CHAR(26)      NOT NULL,
                ticket_id        CHAR(26)      NOT NULL,
                type             VARCHAR(20)   NOT NULL,
                started_at       DATETIME      NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                arrived_at       DATETIME      NULL     COMMENT '(DC2Type:datetime_immutable)',
                is_part_of_route TINYINT(1)    NOT NULL DEFAULT 0,
                notes            LONGTEXT      NULL,
                PRIMARY KEY(id),
                INDEX idx_ticket_journeys_ticket_id (ticket_id),
                CONSTRAINT fk_ticket_journeys_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE ticket_findings (
                id                  CHAR(26)      NOT NULL,
                ticket_id           CHAR(26)      NOT NULL,
                cat_code            VARCHAR(100)  NULL,
                physical_doc_number VARCHAR(100)  NULL,
                problem_type_found  VARCHAR(255)  NOT NULL,
                problem_detail      LONGTEXT      NOT NULL,
                battery_voltage_ok  TINYINT(1)    NOT NULL DEFAULT 1,
                solution            LONGTEXT      NOT NULL,
                registered_by       CHAR(26)      NOT NULL,
                PRIMARY KEY(id),
                UNIQUE INDEX uq_ticket_findings_ticket_id (ticket_id),
                CONSTRAINT fk_ticket_findings_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE ticket_spare_parts (
                id                   CHAR(26)     NOT NULL,
                ticket_id            CHAR(26)     NOT NULL,
                finding_id           CHAR(26)     NOT NULL,
                product_code         VARCHAR(50)  NOT NULL,
                serial_number        VARCHAR(100) NULL,
                quantity             INT          NOT NULL DEFAULT 1,
                is_incoming          TINYINT(1)   NOT NULL DEFAULT 0,
                is_visible_to_client TINYINT(1)   NOT NULL DEFAULT 1,
                auto_marked_damaged  TINYINT(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY(id),
                INDEX idx_ticket_spare_parts_ticket_id  (ticket_id),
                INDEX idx_ticket_spare_parts_finding_id (finding_id),
                CONSTRAINT fk_ticket_spare_parts_ticket  FOREIGN KEY (ticket_id)  REFERENCES tickets (id),
                CONSTRAINT fk_ticket_spare_parts_finding FOREIGN KEY (finding_id) REFERENCES ticket_findings (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE ticket_pending_spare_parts (
                id           CHAR(26)     NOT NULL,
                ticket_id    CHAR(26)     NOT NULL,
                finding_id   CHAR(26)     NOT NULL,
                product_code VARCHAR(50)  NOT NULL,
                quantity     INT          NOT NULL DEFAULT 1,
                notes        LONGTEXT     NULL,
                PRIMARY KEY(id),
                INDEX idx_ticket_pending_spare_parts_ticket_id  (ticket_id),
                INDEX idx_ticket_pending_spare_parts_finding_id (finding_id),
                CONSTRAINT fk_ticket_pending_spare_parts_ticket  FOREIGN KEY (ticket_id)  REFERENCES tickets (id),
                CONSTRAINT fk_ticket_pending_spare_parts_finding FOREIGN KEY (finding_id) REFERENCES ticket_findings (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE ticket_evidences (
                id         CHAR(26)     NOT NULL,
                ticket_id  CHAR(26)     NOT NULL,
                file_path  VARCHAR(500) NOT NULL,
                type       VARCHAR(20)  NOT NULL,
                uploaded_by CHAR(26)   NOT NULL,
                uploaded_at DATETIME   NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id),
                INDEX idx_ticket_evidences_ticket_id (ticket_id),
                CONSTRAINT fk_ticket_evidences_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE ticket_pauses (
                id           CHAR(26)   NOT NULL,
                ticket_id    CHAR(26)   NOT NULL,
                pause_reason LONGTEXT   NOT NULL,
                paused_at    DATETIME   NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                resumed_at   DATETIME   NULL     COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id),
                INDEX idx_ticket_pauses_ticket_id (ticket_id),
                CONSTRAINT fk_ticket_pauses_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE ticket_reschedules (
                id                    CHAR(26)   NOT NULL,
                ticket_id             CHAR(26)   NOT NULL,
                previous_scheduled_at DATETIME   NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                new_scheduled_at      DATETIME   NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                reason                LONGTEXT   NOT NULL,
                requested_by          VARCHAR(10) NOT NULL,
                rescheduled_by        CHAR(26)   NOT NULL,
                rescheduled_at        DATETIME   NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id),
                INDEX idx_ticket_reschedules_ticket_id (ticket_id),
                CONSTRAINT fk_ticket_reschedules_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticket_assignments      DROP FOREIGN KEY fk_ticket_assignments_ticket');
        $this->addSql('ALTER TABLE ticket_journeys         DROP FOREIGN KEY fk_ticket_journeys_ticket');
        $this->addSql('ALTER TABLE ticket_spare_parts      DROP FOREIGN KEY fk_ticket_spare_parts_ticket');
        $this->addSql('ALTER TABLE ticket_spare_parts      DROP FOREIGN KEY fk_ticket_spare_parts_finding');
        $this->addSql('ALTER TABLE ticket_pending_spare_parts DROP FOREIGN KEY fk_ticket_pending_spare_parts_ticket');
        $this->addSql('ALTER TABLE ticket_pending_spare_parts DROP FOREIGN KEY fk_ticket_pending_spare_parts_finding');
        $this->addSql('ALTER TABLE ticket_evidences        DROP FOREIGN KEY fk_ticket_evidences_ticket');
        $this->addSql('ALTER TABLE ticket_pauses           DROP FOREIGN KEY fk_ticket_pauses_ticket');
        $this->addSql('ALTER TABLE ticket_reschedules      DROP FOREIGN KEY fk_ticket_reschedules_ticket');
        $this->addSql('ALTER TABLE ticket_findings         DROP FOREIGN KEY fk_ticket_findings_ticket');
        $this->addSql('DROP TABLE ticket_reschedules');
        $this->addSql('DROP TABLE ticket_pauses');
        $this->addSql('DROP TABLE ticket_evidences');
        $this->addSql('DROP TABLE ticket_pending_spare_parts');
        $this->addSql('DROP TABLE ticket_spare_parts');
        $this->addSql('DROP TABLE ticket_findings');
        $this->addSql('DROP TABLE ticket_journeys');
        $this->addSql('DROP TABLE ticket_assignments');
        $this->addSql('DROP TABLE tickets');
    }
}
