<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Console;

use App\Support\Application\Command\EvaluateScheduledMaintenance\EvaluateScheduledMaintenanceCommand as EvaluateCommand;
use App\Support\Application\Command\EvaluateScheduledMaintenance\EvaluateScheduledMaintenanceHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Evalúa mantenimientos programados próximos a vencer o vencidos.
 *
 * Ejecutar periódicamente via cron (recomendado: diario):
 *   php bin/console support:evaluate-maintenance
 */
#[AsCommand(
    name: 'support:evaluate-maintenance',
    description: 'Evalúa mantenimientos programados y genera tickets para los que están vencidos.',
)]
final class EvaluateScheduledMaintenanceCommand extends Command
{
    public function __construct(
        private readonly EvaluateScheduledMaintenanceHandler $handler,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Evaluación de Mantenimientos Programados');

        $result = ($this->handler)(new EvaluateCommand());

        $io->writeln(sprintf('  <info>Mantenimientos evaluados:</info>     %d', $result->dueMaintenances));
        $io->writeln(sprintf('  <comment>Tickets creados:</comment>        %d', $result->ticketsCreated));
        $io->writeln(sprintf('  <comment>Alertas enviadas:</comment>       %d', $result->alertsSent));
        $io->writeln(sprintf('  <comment>Próximos vencimientos:</comment>  %d', $result->upcomingAlerts));

        $io->success(sprintf(
            '%d tickets de mantenimiento creados, %d alertas generadas.',
            $result->ticketsCreated,
            $result->alertsSent,
        ));

        return Command::SUCCESS;
    }
}
