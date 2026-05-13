<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421184326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE auth_logs (user_id VARCHAR(26) NOT NULL, event VARCHAR(50) NOT NULL, ip_address VARCHAR(45) NOT NULL, user_agent VARCHAR(512) NOT NULL, occurred_at DATETIME NOT NULL, id VARCHAR(26) NOT NULL, INDEX idx_auth_logs_user_id (user_id), INDEX idx_auth_logs_occurred_at (occurred_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE clients (name VARCHAR(100) NOT NULL, ruc VARCHAR(13) NOT NULL, address VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, deleted_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, UNIQUE INDEX UNIQ_C82E742EC7D87D (ruc), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contacts (client_id VARCHAR(26) NOT NULL, user_id VARCHAR(26) DEFAULT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone_number VARCHAR(10) NOT NULL, role VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, deleted_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, UNIQUE INDEX UNIQ_33401573E7927C74 (email), INDEX idx_contacts_client_id (client_id), INDEX idx_contacts_user_id (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE employees (user_id VARCHAR(26) NOT NULL, identification_number VARCHAR(10) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone_number VARCHAR(15) NOT NULL, hired_at DATETIME NOT NULL, terminated_at DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, deleted_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, UNIQUE INDEX UNIQ_BA82C300347639A5 (identification_number), UNIQUE INDEX UNIQ_BA82C300E7927C74 (email), INDEX idx_employees_user_id (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_tokens (user_id VARCHAR(26) NOT NULL, type VARCHAR(30) NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, id VARCHAR(26) NOT NULL, UNIQUE INDEX UNIQ_CF080AB3B3BC57DA (token_hash), INDEX idx_user_tokens_user_id_type (user_id, type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (username VARCHAR(15) NOT NULL, password VARCHAR(255) NOT NULL, role_id VARCHAR(26) NOT NULL, status VARCHAR(20) NOT NULL, failed_login_attempts INT NOT NULL, locked_until DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE auth_logs');
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE contacts');
        $this->addSql('DROP TABLE employees');
        $this->addSql('DROP TABLE user_tokens');
        $this->addSql('DROP TABLE users');
    }
}
