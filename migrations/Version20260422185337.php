<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260422185337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recreate role_permissions join table (accidentally dropped by Version20260422183214)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE role_permissions (
            role_id VARCHAR(26) NOT NULL,
            permission_id VARCHAR(26) NOT NULL,
            INDEX idx_rp_role_id (role_id),
            INDEX idx_rp_permission_id (permission_id),
            PRIMARY KEY (role_id, permission_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE role_permissions');
    }
}
