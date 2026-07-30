<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create support_ticket_module_findings and support_finding_evidences tables for ATM Module Tree';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE support_ticket_module_findings (
            id VARCHAR(26) NOT NULL,
            ticket_id VARCHAR(26) NOT NULL,
            module_path VARCHAR(200) NOT NULL,
            component_name VARCHAR(200) NOT NULL,
            finding_type VARCHAR(20) NOT NULL,
            notes TEXT DEFAULT NULL,
            registered_by VARCHAR(26) NOT NULL,
            registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_tkt_mod_finding_ticket (ticket_id),
            INDEX idx_tkt_mod_finding_type (finding_type),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE support_finding_evidences (
            id VARCHAR(26) NOT NULL,
            finding_id VARCHAR(26) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            moment VARCHAR(10) NOT NULL,
            uploaded_by VARCHAR(26) NOT NULL,
            uploaded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_finding_evidences_finding (finding_id),
            INDEX idx_finding_evidences_moment (moment),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE support_ticket_module_findings
            ADD CONSTRAINT FK_ticket_module_findings_ticket
            FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE support_finding_evidences
            ADD CONSTRAINT FK_finding_evidences_finding
            FOREIGN KEY (finding_id) REFERENCES support_ticket_module_findings (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_finding_evidences DROP FOREIGN KEY FK_finding_evidences_finding');
        $this->addSql('ALTER TABLE support_ticket_module_findings DROP FOREIGN KEY FK_ticket_module_findings_ticket');
        $this->addSql('DROP TABLE support_finding_evidences');
        $this->addSql('DROP TABLE support_ticket_module_findings');
    }
}
