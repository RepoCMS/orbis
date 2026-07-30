<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Console;

use App\Support\Application\Command\MonitorSla\MonitorSlaCommand as MonitorSlaMessage;
use App\Support\Application\Command\MonitorSla\MonitorSlaHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Monitorea tickets con SLA próximo a vencer o vencido.
 *
 * Ejecutar periódicamente via cron (recomendado: cada 15 minutos):
 *   php bin/console support:monitor-sla
 *
 * Lógica delegada a MonitorSlaHandler (Application Layer).
 */
#[AsCommand(
    name: 'support:monitor-sla',
    description: 'Monitorea tickets con SLA próximo a vencer o vencido y genera alertas.',
)]
final class MonitorSlaCommand extends Command
{
    public function __construct(
        private readonly MonitorSlaHandler $handler,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Monitoreo de SLA de Tickets');

        $result = ($this->handler)(new MonitorSlaMessage());

        $io->writeln(sprintf('  <info>Tickets monitoreados:</info>        %d', $result->totalTickets));
        $io->writeln(sprintf('  <comment>Alertas SLA próximo a vencer:</comment> %d', $result->aboutToExpireAlerts));
        $io->writeln(sprintf('  <comment>Alertas SLA crítico:</comment>       %d', $result->criticalAlerts));
        $io->writeln(sprintf('  <comment>Alertas SLA vencido:</comment>       %d', $result->breachedAlerts));

        $io->success(sprintf(
            '%d tickets monitoreados, %d alertas generadas.',
            $result->totalTickets,
            $result->totalAlerts(),
        ));

        return Command::SUCCESS;
    }
}
