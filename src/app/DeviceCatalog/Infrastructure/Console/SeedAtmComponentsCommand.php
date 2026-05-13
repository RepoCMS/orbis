<?php

declare(strict_types=1);

namespace App\DeviceCatalog\Infrastructure\Console;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Ulid;

#[AsCommand(name: 'app:seed:atm-components', description: 'Seed ATM component catalog')]
final class SeedAtmComponentsCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seeding ATM component catalog');

        $existing = $this->connection->fetchOne('SELECT COUNT(*) FROM atm_components');
        if ((int) $existing > 0) {
            $io->warning('Catalog already has data. Skipping.');
            return Command::SUCCESS;
        }

        $devices = [
            ['name' => 'CDM / BRM',         'aliases' => 'CDM,BRM,RCC',         'sort' => 10],
            ['name' => 'Dispensador',        'aliases' => 'GBRU,GBNA,Dispenser', 'sort' => 20],
            ['name' => 'CPU / Mainboard',    'aliases' => 'CPU,Placa base',      'sort' => 30],
            ['name' => 'Monitor / Pantalla', 'aliases' => 'Monitor,Pantalla',    'sort' => 40],
            ['name' => 'Gabinete / Caja',   'aliases' => 'Gabinete,Carcasa',    'sort' => 50],
            ['name' => 'Lectora de tarjetas','aliases' => 'Card reader,Lectora', 'sort' => 60],
            ['name' => 'Impresora recibos',  'aliases' => 'Printer,Impresora',   'sort' => 70],
            ['name' => 'NFC / Contactless',  'aliases' => 'NFC,Contactless',     'sort' => 80],
            ['name' => 'Cámara',             'aliases' => 'Camera,Cámara ATM',   'sort' => 90],
            ['name' => 'UPS / Batería',      'aliases' => 'UPS,Battery,Batería', 'sort' => 100],
            ['name' => 'Red / Conectividad', 'aliases' => 'Network,LAN,Modem',   'sort' => 110],
            ['name' => 'EPP / Pinpad',       'aliases' => 'EPP,Pinpad,Teclado',  'sort' => 120],
        ];

        $modulesByDevice = [
            'CDM / BRM' => [
                ['name' => 'Módulo de aceptación', 'aliases' => 'Accept module', 'sort' => 10],
                ['name' => 'Módulo de rechazo',    'aliases' => 'Reject module', 'sort' => 20],
                ['name' => 'Cassette',             'aliases' => 'Cassette CDM',  'sort' => 30],
                ['name' => 'Sensor billetes',      'aliases' => 'Bill sensor',   'sort' => 40],
            ],
            'Dispensador' => [
                ['name' => 'Cassette efectivo', 'aliases' => 'Cash cassette',     'sort' => 10],
                ['name' => 'Módulo transporte', 'aliases' => 'Transport module',  'sort' => 20],
                ['name' => 'Sensor dispensador','aliases' => 'Dispenser sensor',  'sort' => 30],
            ],
        ];

        $deviceIds = [];
        $this->connection->beginTransaction();
        try {
            foreach ($devices as $d) {
                $id = (string) new Ulid();
                $this->connection->executeStatement(
                    'INSERT INTO atm_components (id, parent_id, name, aliases, level, sort_order, active)
                     VALUES (?, NULL, ?, ?, \'device\', ?, 1)',
                    [$id, $d['name'], $d['aliases'], $d['sort']]
                );
                $deviceIds[$d['name']] = $id;
                $io->writeln("  + Device: {$d['name']}");
            }

            foreach ($modulesByDevice as $deviceName => $modules) {
                $parentId = $deviceIds[$deviceName] ?? null;
                if (!$parentId) {
                    continue;
                }
                foreach ($modules as $m) {
                    $id = (string) new Ulid();
                    $this->connection->executeStatement(
                        'INSERT INTO atm_components (id, parent_id, name, aliases, level, sort_order, active)
                         VALUES (?, ?, ?, ?, \'module\', ?, 1)',
                        [$id, $parentId, $m['name'], $m['aliases'], $m['sort']]
                    );
                    $io->writeln("    + Module [{$deviceName}]: {$m['name']}");
                }
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            $io->error('Seed failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success('ATM component catalog seeded successfully.');
        return Command::SUCCESS;
    }
}
