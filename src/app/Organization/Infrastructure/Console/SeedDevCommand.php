<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:dev',
    description: 'Orquestador: ejecuta todos los seeds de producción + datos de desarrollo.',
)]
final class SeedDevCommand extends Command
{
    private static function devPassword(): string
    {
        $password = getenv('ORBIS_DEV_SEED_PASSWORD');
        if ($password === false || $password === '') {
            throw new \RuntimeException('Define ORBIS_DEV_SEED_PASSWORD en el entorno para ejecutar el seed dev.');
        }

        return $password;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Orbis — Dev Seed Orchestrator');
        $io->text('Ejecutando seeds de producción + datos de desarrollo...');
        $io->newLine();

        $prodSeeds = [
            ['app:seed:geography', 'Geografía de Ecuador'],
            ['app:permissions:seed', 'Permisos del sistema'],
        ];

        foreach ($prodSeeds as [$cmd, $label]) {
            $io->section($label);
            $this->runSeedCommand($cmd, $input, $output);
        }

        $io->section('Admin inicial');
        $this->runSeedCommand('app:seed:admin', $input, $output, [
            '--password' => self::devPassword(),
        ]);

        $io->section('Roles predefinidos');
        $this->runSeedCommand('app:roles:seed', $input, $output);

        $io->section('Plantillas de notificación');
        $this->runSeedCommand('orbis:notifications:seed-templates', $input, $output);

        $io->section('Permisos de inventario para Administrador');
        $this->runSeedCommand('app:inventory:assign-permissions', $input, $output, [
            'role' => 'Administrador',
        ]);

        $io->section('Datos completos de desarrollo');
        $this->runSeedCommand('app:seed:full', $input, $output);

        $io->newLine(2);
        $io->success('Dev seed completado. Datos listos para desarrollo.');
        $io->table(
            ['Usuario', 'Password', 'Rol'],
            [
                ['admin', self::devPassword(), 'Administrador'],
                ['carlos', self::devPassword(), 'Técnico'],
            ],
        );

        return Command::SUCCESS;
    }

    private function runSeedCommand(string $commandName, InputInterface $input, OutputInterface $output, array $extraParams = []): void
    {
        try {
            $params = array_merge(['--quiet' => true, '--no-interaction' => true], $extraParams);
            $cmd = $this->getApplication()->find($commandName);
            $cmd->run(new ArrayInput($params), $output);
        } catch (\Throwable $e) {
            $output->writeln(sprintf('  <comment>[!] %s: %s</comment>', $commandName, $e->getMessage()));
        }
    }
}
