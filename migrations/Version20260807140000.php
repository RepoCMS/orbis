<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create notification_logs, notification_templates, notification_preferences tables
 */
final class Version20260807140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification tables: notification_logs, notification_templates, notification_preferences';
    }

    public function up(Schema $schema): void
    {
        // notification_logs
        $this->addSql('CREATE TABLE notification_logs (
            id VARCHAR(26) NOT NULL,
            recipient_type VARCHAR(20) NOT NULL,
            recipient_id VARCHAR(26) NOT NULL,
            channel VARCHAR(20) NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            subject VARCHAR(255) DEFAULT NULL,
            body LONGTEXT NOT NULL,
            reference_type VARCHAR(50) DEFAULT NULL,
            reference_id VARCHAR(26) DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            attempt_count INT NOT NULL,
            last_attempt_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            failure_reason LONGTEXT DEFAULT NULL,
            read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_notification_logs_recipient (recipient_type, recipient_id),
            INDEX idx_notification_logs_status (status),
            INDEX idx_notification_logs_event_type (event_type),
            INDEX idx_notification_logs_reference (reference_type, reference_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // notification_templates
        $this->addSql('CREATE TABLE notification_templates (
            id VARCHAR(26) NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            channel VARCHAR(20) NOT NULL,
            subject VARCHAR(255) DEFAULT NULL,
            body_template LONGTEXT NOT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uq_notification_templates_event_channel (event_type, channel),
            INDEX idx_notification_templates_event_type (event_type),
            INDEX idx_notification_templates_channel (channel),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // notification_preferences
        $this->addSql('CREATE TABLE notification_preferences (
            id VARCHAR(26) NOT NULL,
            recipient_type VARCHAR(20) NOT NULL,
            recipient_id VARCHAR(26) NOT NULL,
            channel VARCHAR(20) NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE INDEX uq_notification_preferences_recipient_event_channel (recipient_type, recipient_id, channel, event_type),
            INDEX idx_notification_preferences_recipient (recipient_type, recipient_id),
            INDEX idx_notification_preferences_event (event_type),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification_preferences');
        $this->addSql('DROP TABLE notification_templates');
        $this->addSql('DROP TABLE notification_logs');
    }
}