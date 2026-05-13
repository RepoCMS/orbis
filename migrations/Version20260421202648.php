<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421202648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE permissions (permission_key VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, id VARCHAR(26) NOT NULL, UNIQUE INDEX UNIQ_2DEDCC6F9A0B3198 (permission_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE roles (name VARCHAR(60) NOT NULL, status VARCHAR(20) NOT NULL, id VARCHAR(26) NOT NULL, UNIQUE INDEX UNIQ_B63E2EC75E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role_permissions (role_id VARCHAR(26) NOT NULL, permission_id VARCHAR(26) NOT NULL, INDEX idx_rp_role_id (role_id), INDEX idx_rp_permission_id (permission_id), PRIMARY KEY (role_id, permission_id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE role_permissions');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE roles');
    }
}
