<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * REQ-ID-001c: Crea tabla user_mfa_configs (UserMfaConfig entity).
 * Elimina columnas email y totp_secret de users (deuda técnica REQ-ID-001).
 */
final class Version20260504000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_mfa_configs; drop email and totp_secret from users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user_mfa_configs (
                id          VARCHAR(26)  NOT NULL,
                user_id     VARCHAR(26)  NOT NULL,
                secret      VARCHAR(255) NOT NULL,
                is_enabled  TINYINT(1)   NOT NULL DEFAULT 0,
                confirmed_at DATETIME     NULL,
                created_at  DATETIME     NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_user_mfa_configs_user_id (user_id),
                KEY idx_user_mfa_configs_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        // Eliminar columnas de deuda técnica (REQ-ID-001)
        $this->addSql('ALTER TABLE users DROP COLUMN email');
        $this->addSql('ALTER TABLE users DROP COLUMN totp_secret');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_mfa_configs');

        $this->addSql('ALTER TABLE users ADD COLUMN email VARCHAR(180) NULL');
        $this->addSql('ALTER TABLE users ADD COLUMN totp_secret VARCHAR(255) NULL');
    }
}
