<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Alinea tipos de columnas con lo que genera Doctrine (CHAR→VARCHAR) y
 * renombra el índice de service_zones.name para coincidir con la convención.
 */
final class Version20260511000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align column types CHAR→VARCHAR for Doctrine compatibility; rename service_zones name index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sites CHANGE id id VARCHAR(26) NOT NULL, CHANGE client_id client_id VARCHAR(26) NOT NULL, CHANGE city_id city_id VARCHAR(26) NOT NULL, CHANGE terminal_id terminal_id VARCHAR(26) NOT NULL, CHANGE status status VARCHAR(10) NOT NULL, CHANGE service_zone_id service_zone_id VARCHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE employees CHANGE profile profile VARCHAR(20) NOT NULL, CHANGE service_zone_id service_zone_id VARCHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE service_zones CHANGE id id VARCHAR(26) NOT NULL, CHANGE status status VARCHAR(10) NOT NULL, CHANGE city_ids city_ids JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE service_zones RENAME INDEX uniq_service_zones_name TO UNIQ_2CB85C8F5E237E06');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sites CHANGE id id CHAR(26) NOT NULL, CHANGE client_id client_id CHAR(26) NOT NULL, CHANGE city_id city_id CHAR(26) NOT NULL, CHANGE terminal_id terminal_id CHAR(26) NOT NULL, CHANGE status status VARCHAR(10) NOT NULL, CHANGE service_zone_id service_zone_id CHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE employees CHANGE profile profile VARCHAR(20) NOT NULL, CHANGE service_zone_id service_zone_id CHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE service_zones CHANGE id id CHAR(26) NOT NULL, CHANGE status status VARCHAR(10) NOT NULL, CHANGE city_ids city_ids JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE service_zones RENAME INDEX UNIQ_2CB85C8F5E237E06 TO uniq_service_zones_name');
    }
}
