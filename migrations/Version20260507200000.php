<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates regions, provinces, and cities tables with Ecuador seed data.
 */
final class Version20260507200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create regions, provinces, cities tables and seed Ecuador data';
    }

    public function up(Schema $schema): void
    {
        // Tables
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

        // ── Regions ──────────────────────────────────────────────────────────
        $this->addSql("INSERT INTO regions (id, name) VALUES
            ('01RGN000000000000000000001', 'Costa'),
            ('01RGN000000000000000000002', 'Sierra'),
            ('01RGN000000000000000000003', 'Oriente'),
            ('01RGN000000000000000000004', 'Region Insular')
        ");

        // ── Provinces ─────────────────────────────────────────────────────────
        $this->addSql("INSERT INTO provinces (id, region_id, name) VALUES
            -- Costa
            ('01PRV000000000000000000001', '01RGN000000000000000000001', 'El Oro'),
            ('01PRV000000000000000000002', '01RGN000000000000000000001', 'Esmeraldas'),
            ('01PRV000000000000000000003', '01RGN000000000000000000001', 'Guayas'),
            ('01PRV000000000000000000004', '01RGN000000000000000000001', 'Los Rios'),
            ('01PRV000000000000000000005', '01RGN000000000000000000001', 'Manabi'),
            ('01PRV000000000000000000006', '01RGN000000000000000000001', 'Santa Elena'),
            ('01PRV000000000000000000007', '01RGN000000000000000000001', 'Santo Domingo de los Tsachilas'),
            -- Sierra
            ('01PRV000000000000000000008', '01RGN000000000000000000002', 'Azuay'),
            ('01PRV000000000000000000009', '01RGN000000000000000000002', 'Bolivar'),
            ('01PRV000000000000000000010', '01RGN000000000000000000002', 'Canar'),
            ('01PRV000000000000000000011', '01RGN000000000000000000002', 'Carchi'),
            ('01PRV000000000000000000012', '01RGN000000000000000000002', 'Chimborazo'),
            ('01PRV000000000000000000013', '01RGN000000000000000000002', 'Cotopaxi'),
            ('01PRV000000000000000000014', '01RGN000000000000000000002', 'Imbabura'),
            ('01PRV000000000000000000015', '01RGN000000000000000000002', 'Loja'),
            ('01PRV000000000000000000016', '01RGN000000000000000000002', 'Pichincha'),
            ('01PRV000000000000000000017', '01RGN000000000000000000002', 'Tungurahua'),
            -- Oriente
            ('01PRV000000000000000000018', '01RGN000000000000000000003', 'Morona Santiago'),
            ('01PRV000000000000000000019', '01RGN000000000000000000003', 'Napo'),
            ('01PRV000000000000000000020', '01RGN000000000000000000003', 'Orellana'),
            ('01PRV000000000000000000021', '01RGN000000000000000000003', 'Pastaza'),
            ('01PRV000000000000000000022', '01RGN000000000000000000003', 'Sucumbios'),
            ('01PRV000000000000000000023', '01RGN000000000000000000003', 'Zamora Chinchipe'),
            -- Insular
            ('01PRV000000000000000000024', '01RGN000000000000000000004', 'Galapagos')
        ");

        // ── Cities (capitales provinciales + ciudades principales) ────────────
        $this->addSql("INSERT INTO cities (id, province_id, name) VALUES
            -- El Oro
            ('01CTY000000000000000000001', '01PRV000000000000000000001', 'Machala'),
            ('01CTY000000000000000000002', '01PRV000000000000000000001', 'Santa Rosa'),
            -- Esmeraldas
            ('01CTY000000000000000000003', '01PRV000000000000000000002', 'Esmeraldas'),
            -- Guayas
            ('01CTY000000000000000000004', '01PRV000000000000000000003', 'Guayaquil'),
            ('01CTY000000000000000000005', '01PRV000000000000000000003', 'Daule'),
            ('01CTY000000000000000000006', '01PRV000000000000000000003', 'Milagro'),
            ('01CTY000000000000000000007', '01PRV000000000000000000003', 'Samborondon'),
            ('01CTY000000000000000000008', '01PRV000000000000000000003', 'Duran'),
            -- Los Rios
            ('01CTY000000000000000000009', '01PRV000000000000000000004', 'Babahoyo'),
            ('01CTY000000000000000000010', '01PRV000000000000000000004', 'Quevedo'),
            -- Manabi
            ('01CTY000000000000000000011', '01PRV000000000000000000005', 'Portoviejo'),
            ('01CTY000000000000000000012', '01PRV000000000000000000005', 'Manta'),
            ('01CTY000000000000000000013', '01PRV000000000000000000005', 'Chone'),
            -- Santa Elena
            ('01CTY000000000000000000014', '01PRV000000000000000000006', 'Santa Elena'),
            ('01CTY000000000000000000015', '01PRV000000000000000000006', 'La Libertad'),
            -- Santo Domingo
            ('01CTY000000000000000000016', '01PRV000000000000000000007', 'Santo Domingo'),
            -- Azuay
            ('01CTY000000000000000000017', '01PRV000000000000000000008', 'Cuenca'),
            -- Bolivar
            ('01CTY000000000000000000018', '01PRV000000000000000000009', 'Guaranda'),
            -- Canar
            ('01CTY000000000000000000019', '01PRV000000000000000000010', 'Azogues'),
            -- Carchi
            ('01CTY000000000000000000020', '01PRV000000000000000000011', 'Tulcan'),
            -- Chimborazo
            ('01CTY000000000000000000021', '01PRV000000000000000000012', 'Riobamba'),
            -- Cotopaxi
            ('01CTY000000000000000000022', '01PRV000000000000000000013', 'Latacunga'),
            -- Imbabura
            ('01CTY000000000000000000023', '01PRV000000000000000000014', 'Ibarra'),
            ('01CTY000000000000000000024', '01PRV000000000000000000014', 'Otavalo'),
            -- Loja
            ('01CTY000000000000000000025', '01PRV000000000000000000015', 'Loja'),
            -- Pichincha
            ('01CTY000000000000000000026', '01PRV000000000000000000016', 'Quito'),
            ('01CTY000000000000000000027', '01PRV000000000000000000016', 'Sangolqui'),
            -- Tungurahua
            ('01CTY000000000000000000028', '01PRV000000000000000000017', 'Ambato'),
            -- Morona Santiago
            ('01CTY000000000000000000029', '01PRV000000000000000000018', 'Macas'),
            -- Napo
            ('01CTY000000000000000000030', '01PRV000000000000000000019', 'Tena'),
            -- Orellana
            ('01CTY000000000000000000031', '01PRV000000000000000000020', 'Francisco de Orellana'),
            -- Pastaza
            ('01CTY000000000000000000032', '01PRV000000000000000000021', 'Puyo'),
            -- Sucumbios
            ('01CTY000000000000000000033', '01PRV000000000000000000022', 'Nueva Loja'),
            -- Zamora Chinchipe
            ('01CTY000000000000000000034', '01PRV000000000000000000023', 'Zamora'),
            -- Galapagos
            ('01CTY000000000000000000035', '01PRV000000000000000000024', 'Puerto Baquerizo Moreno')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM cities  WHERE id LIKE \'01CTY%\'');
        $this->addSql('DELETE FROM provinces WHERE id LIKE \'01PRV%\'');
        $this->addSql('DELETE FROM regions  WHERE id LIKE \'01RGN%\'');
        $this->addSql('DROP TABLE cities');
        $this->addSql('DROP TABLE provinces');
        $this->addSql('DROP TABLE regions');
    }
}
