<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates regions, provinces, and cities tables (seeding moved to app:seed:geography for real ULIDs).
 */
final class Version20260507200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create regions, provinces, cities tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE regions (
                id   VARCHAR(26)  NOT NULL,
                name VARCHAR(100) NOT NULL,
                UNIQUE INDEX idx_regions_name (name),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ");

        $this->addSql("
            CREATE TABLE provinces (
                id        VARCHAR(26)  NOT NULL,
                region_id VARCHAR(26)  NOT NULL,
                name      VARCHAR(150) NOT NULL,
                INDEX idx_provinces_region_id (region_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ");

        $this->addSql("
            CREATE TABLE cities (
                id          VARCHAR(26)  NOT NULL,
                province_id VARCHAR(26)  NOT NULL,
                name        VARCHAR(150) NOT NULL,
                INDEX idx_cities_province_id (province_id),
                INDEX idx_cities_name (name),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cities');
        $this->addSql('DROP TABLE provinces');
        $this->addSql('DROP TABLE regions');
    }
}
