<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at and updated_at columns to clients table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE clients
                ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'(DC2Type:datetime_immutable)\',
                ADD COLUMN updated_at DATETIME NULL COMMENT \'(DC2Type:datetime_immutable)\''
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients DROP COLUMN created_at, DROP COLUMN updated_at');
    }
}
