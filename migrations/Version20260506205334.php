<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506205334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE terminal_assignments (terminal_id VARCHAR(26) NOT NULL, name VARCHAR(100) NOT NULL, id_assigned_by_client VARCHAR(25) NOT NULL, site_id VARCHAR(26) NOT NULL, status VARCHAR(20) NOT NULL, deleted_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, INDEX idx_terminal_assignments_terminal_id (terminal_id), INDEX idx_terminal_assignments_site_id (site_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE terminals (client_id VARCHAR(26) NOT NULL, serial VARCHAR(100) NOT NULL, vendor VARCHAR(100) NOT NULL, funcionality VARCHAR(100) NOT NULL, type VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, deleted_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, INDEX idx_terminals_client_id (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE clients CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_contacts_user_id ON contacts');
        $this->addSql('ALTER TABLE employee_special_periods CHANGE id id VARCHAR(26) NOT NULL, CHANGE employee_id employee_id VARCHAR(26) NOT NULL, CHANGE type type VARCHAR(10) NOT NULL, CHANGE status status VARCHAR(12) NOT NULL');
        $this->addSql('DROP INDEX uniq_employees_user_id ON employees');
        $this->addSql('DROP INDEX idx_user_mfa_configs_user_id ON user_mfa_configs');
        $this->addSql('ALTER TABLE user_mfa_configs CHANGE is_enabled is_enabled TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE terminal_assignments');
        $this->addSql('DROP TABLE terminals');
        $this->addSql('ALTER TABLE clients CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_contacts_user_id ON contacts (user_id)');
        $this->addSql('ALTER TABLE employee_special_periods CHANGE employee_id employee_id CHAR(26) NOT NULL, CHANGE type type VARCHAR(10) NOT NULL COMMENT \'vacation | standby\', CHANGE status status VARCHAR(12) DEFAULT \'scheduled\' NOT NULL COMMENT \'scheduled | in_progress | completed | cancelled\', CHANGE id id CHAR(26) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_employees_user_id ON employees (user_id)');
        $this->addSql('ALTER TABLE user_mfa_configs CHANGE is_enabled is_enabled TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_user_mfa_configs_user_id ON user_mfa_configs (user_id)');
    }
}
