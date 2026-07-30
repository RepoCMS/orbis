<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512211454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Phase 1 – drop all existing FKs first (before CHANGE on referenced columns)
        $this->addSql('ALTER TABLE ticket_assignments DROP FOREIGN KEY `fk_ticket_assignments_ticket`');
        $this->addSql('ALTER TABLE ticket_evidences DROP FOREIGN KEY `fk_ticket_evidences_ticket`');
        $this->addSql('ALTER TABLE ticket_findings DROP FOREIGN KEY `fk_ticket_findings_ticket`');
        $this->addSql('ALTER TABLE ticket_journeys DROP FOREIGN KEY `fk_ticket_journeys_ticket`');
        $this->addSql('ALTER TABLE ticket_pauses DROP FOREIGN KEY `fk_ticket_pauses_ticket`');
        $this->addSql('ALTER TABLE ticket_pending_spare_parts DROP FOREIGN KEY `fk_ticket_pending_spare_parts_finding`');
        $this->addSql('ALTER TABLE ticket_pending_spare_parts DROP FOREIGN KEY `fk_ticket_pending_spare_parts_ticket`');
        $this->addSql('ALTER TABLE ticket_reschedules DROP FOREIGN KEY `fk_ticket_reschedules_ticket`');
        $this->addSql('ALTER TABLE ticket_spare_parts DROP FOREIGN KEY `fk_ticket_spare_parts_finding`');
        $this->addSql('ALTER TABLE ticket_spare_parts DROP FOREIGN KEY `fk_ticket_spare_parts_ticket`');

        // Phase 2 – drop indexes/columns that must go before CHANGE
        $this->addSql('DROP INDEX idx_ticket_pending_spare_parts_finding_id ON ticket_pending_spare_parts');
        $this->addSql('ALTER TABLE ticket_pending_spare_parts DROP finding_id');

        // Phase 3 – normalise all columns
        $this->addSql('ALTER TABLE ticket_assignments CHANGE id id VARCHAR(26) NOT NULL, CHANGE ticket_id ticket_id VARCHAR(26) NOT NULL, CHANGE employee_id employee_id VARCHAR(26) NOT NULL, CHANGE is_in_charge is_in_charge TINYINT NOT NULL, CHANGE assigned_at assigned_at DATETIME NOT NULL, CHANGE reassigned_from reassigned_from VARCHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_evidences CHANGE id id VARCHAR(26) NOT NULL, CHANGE ticket_id ticket_id VARCHAR(26) NOT NULL, CHANGE uploaded_by uploaded_by VARCHAR(26) NOT NULL, CHANGE uploaded_at uploaded_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE ticket_findings CHANGE id id VARCHAR(26) NOT NULL, CHANGE ticket_id ticket_id VARCHAR(26) NOT NULL, CHANGE problem_type_found problem_type_found VARCHAR(100) NOT NULL, CHANGE battery_voltage_ok battery_voltage_ok TINYINT NOT NULL, CHANGE registered_by registered_by VARCHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE ticket_journeys CHANGE id id VARCHAR(26) NOT NULL, CHANGE ticket_id ticket_id VARCHAR(26) NOT NULL, CHANGE started_at started_at DATETIME NOT NULL, CHANGE arrived_at arrived_at DATETIME DEFAULT NULL, CHANGE is_part_of_route is_part_of_route TINYINT NOT NULL');
        $this->addSql('ALTER TABLE ticket_pauses CHANGE id id VARCHAR(26) NOT NULL, CHANGE ticket_id ticket_id VARCHAR(26) NOT NULL, CHANGE paused_at paused_at DATETIME NOT NULL, CHANGE resumed_at resumed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_pending_spare_parts CHANGE id id VARCHAR(26) NOT NULL, CHANGE ticket_id ticket_id VARCHAR(26) NOT NULL, CHANGE quantity quantity INT NOT NULL');
        $this->addSql('ALTER TABLE ticket_reschedules CHANGE id id VARCHAR(26) NOT NULL, CHANGE ticket_id ticket_id VARCHAR(26) NOT NULL, CHANGE previous_scheduled_at previous_scheduled_at DATETIME NOT NULL, CHANGE new_scheduled_at new_scheduled_at DATETIME NOT NULL, CHANGE rescheduled_by rescheduled_by VARCHAR(26) NOT NULL, CHANGE rescheduled_at rescheduled_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE ticket_spare_parts CHANGE id id VARCHAR(26) NOT NULL, CHANGE ticket_id ticket_id VARCHAR(26) NOT NULL, CHANGE finding_id finding_id VARCHAR(26) NOT NULL, CHANGE quantity quantity INT NOT NULL, CHANGE is_incoming is_incoming TINYINT NOT NULL, CHANGE is_visible_to_client is_visible_to_client TINYINT NOT NULL, CHANGE auto_marked_damaged auto_marked_damaged TINYINT NOT NULL');
        $this->addSql('ALTER TABLE tickets CHANGE id id VARCHAR(26) NOT NULL, CHANGE client_id client_id VARCHAR(26) NOT NULL, CHANGE terminal_assignment_id terminal_assignment_id VARCHAR(26) NOT NULL, CHANGE created_by created_by VARCHAR(26) NOT NULL, CHANGE scheduled_at scheduled_at DATETIME DEFAULT NULL, CHANGE sla_deadline_at sla_deadline_at DATETIME DEFAULT NULL, CHANGE in_transit_out_at in_transit_out_at DATETIME DEFAULT NULL, CHANGE waiting_start_at waiting_start_at DATETIME DEFAULT NULL, CHANGE assistance_start_at assistance_start_at DATETIME DEFAULT NULL, CHANGE in_transit_back_at in_transit_back_at DATETIME DEFAULT NULL, CHANGE closed_at closed_at DATETIME DEFAULT NULL, CHANGE cancelled_at cancelled_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');

        // Phase 4 – create/rename indexes
        $this->addSql('CREATE UNIQUE INDEX UNIQ_517ABF006A26C729 ON ticket_findings (cat_code)');
        $this->addSql('CREATE INDEX idx_ticket_findings_registered_by ON ticket_findings (registered_by)');
        $this->addSql('ALTER TABLE ticket_findings RENAME INDEX uq_ticket_findings_ticket_id TO UNIQ_517ABF00700047D2');
        $this->addSql('CREATE INDEX idx_ticket_spare_parts_product_code ON ticket_spare_parts (product_code)');
        $this->addSql('ALTER TABLE tickets RENAME INDEX uq_tickets_number TO UNIQ_54469DF4ECD2759F');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_assignments CHANGE ticket_id ticket_id CHAR(26) NOT NULL, CHANGE employee_id employee_id CHAR(26) NOT NULL, CHANGE is_in_charge is_in_charge TINYINT DEFAULT 0 NOT NULL, CHANGE assigned_at assigned_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE reassigned_from reassigned_from CHAR(26) DEFAULT NULL, CHANGE id id CHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE ticket_assignments ADD CONSTRAINT `fk_ticket_assignments_ticket` FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ticket_evidences ADD type VARCHAR(20) NOT NULL, CHANGE ticket_id ticket_id CHAR(26) NOT NULL, CHANGE uploaded_by uploaded_by CHAR(26) NOT NULL, CHANGE uploaded_at uploaded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id id CHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE ticket_evidences ADD CONSTRAINT `fk_ticket_evidences_ticket` FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP INDEX UNIQ_517ABF006A26C729 ON ticket_findings');
        $this->addSql('DROP INDEX idx_ticket_findings_ticket_id ON ticket_findings');
        $this->addSql('DROP INDEX idx_ticket_findings_registered_by ON ticket_findings');
        $this->addSql('ALTER TABLE ticket_findings CHANGE ticket_id ticket_id CHAR(26) NOT NULL, CHANGE problem_type_found problem_type_found VARCHAR(255) NOT NULL, CHANGE battery_voltage_ok battery_voltage_ok TINYINT DEFAULT 1 NOT NULL, CHANGE registered_by registered_by CHAR(26) NOT NULL, CHANGE id id CHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE ticket_findings ADD CONSTRAINT `fk_ticket_findings_ticket` FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ticket_findings RENAME INDEX uniq_517abf00700047d2 TO uq_ticket_findings_ticket_id');
        $this->addSql('ALTER TABLE ticket_journeys CHANGE ticket_id ticket_id CHAR(26) NOT NULL, CHANGE started_at started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE arrived_at arrived_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE is_part_of_route is_part_of_route TINYINT DEFAULT 0 NOT NULL, CHANGE id id CHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE ticket_journeys ADD CONSTRAINT `fk_ticket_journeys_ticket` FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ticket_pauses CHANGE ticket_id ticket_id CHAR(26) NOT NULL, CHANGE paused_at paused_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE resumed_at resumed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id id CHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE ticket_pauses ADD CONSTRAINT `fk_ticket_pauses_ticket` FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ticket_pending_spare_parts ADD finding_id CHAR(26) NOT NULL, CHANGE ticket_id ticket_id CHAR(26) NOT NULL, CHANGE quantity quantity INT DEFAULT 1 NOT NULL, CHANGE id id CHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE ticket_pending_spare_parts ADD CONSTRAINT `fk_ticket_pending_spare_parts_finding` FOREIGN KEY (finding_id) REFERENCES ticket_findings (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ticket_pending_spare_parts ADD CONSTRAINT `fk_ticket_pending_spare_parts_ticket` FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX idx_ticket_pending_spare_parts_finding_id ON ticket_pending_spare_parts (finding_id)');
        $this->addSql('ALTER TABLE ticket_reschedules CHANGE ticket_id ticket_id CHAR(26) NOT NULL, CHANGE previous_scheduled_at previous_scheduled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE new_scheduled_at new_scheduled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE rescheduled_by rescheduled_by CHAR(26) NOT NULL, CHANGE rescheduled_at rescheduled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id id CHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE ticket_reschedules ADD CONSTRAINT `fk_ticket_reschedules_ticket` FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP INDEX idx_ticket_spare_parts_product_code ON ticket_spare_parts');
        $this->addSql('ALTER TABLE ticket_spare_parts CHANGE ticket_id ticket_id CHAR(26) NOT NULL, CHANGE finding_id finding_id CHAR(26) NOT NULL, CHANGE quantity quantity INT DEFAULT 1 NOT NULL, CHANGE is_incoming is_incoming TINYINT DEFAULT 0 NOT NULL, CHANGE is_visible_to_client is_visible_to_client TINYINT DEFAULT 1 NOT NULL, CHANGE auto_marked_damaged auto_marked_damaged TINYINT DEFAULT 0 NOT NULL, CHANGE id id CHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE ticket_spare_parts ADD CONSTRAINT `fk_ticket_spare_parts_finding` FOREIGN KEY (finding_id) REFERENCES ticket_findings (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ticket_spare_parts ADD CONSTRAINT `fk_ticket_spare_parts_ticket` FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tickets CHANGE client_id client_id CHAR(26) NOT NULL, CHANGE terminal_assignment_id terminal_assignment_id CHAR(26) NOT NULL, CHANGE created_by created_by CHAR(26) NOT NULL, CHANGE scheduled_at scheduled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE sla_deadline_at sla_deadline_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE in_transit_out_at in_transit_out_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE waiting_start_at waiting_start_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE assistance_start_at assistance_start_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE in_transit_back_at in_transit_back_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE closed_at closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE cancelled_at cancelled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE deleted_at deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id id CHAR(26) NOT NULL');
        $this->addSql('ALTER TABLE tickets RENAME INDEX uniq_54469df4ecd2759f TO uq_tickets_number');
    }
}
