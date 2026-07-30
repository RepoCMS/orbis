<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Console;

use App\Client\Application\Command\ImportTerminals\ImportTerminalsCommand;
use App\Client\Application\Command\ImportTerminals\ImportTerminalsHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'client:import:terminals',
    description: 'Importa terminales desde un archivo CSV.',
)]
final class ClientImportTerminalsCliCommand extends Command
{
    public function __construct(
        private readonly ImportTerminalsHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Ruta al archivo CSV')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Solo previsualizar sin persistir')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getArgument('file');
        $dryRun = (bool) $input->getOption('dry-run');

        $output->writeln(sprintf('Importando terminales desde: %s', $filePath));

        $result = ($this->handler)(new ImportTerminalsCommand(
            filePath: $filePath,
            dryRun: $dryRun,
        ));

        $output->writeln(sprintf(
            'Resultado: %d importados, %d errores, %d omitidos',
            $result->imported,
            $result->errors,
            $result->skipped,
        ));

        if (!empty($result->errorDetail)) {
            $output->writeln('Detalle de errores:');
            foreach ($result->errorDetail as $detail) {
                $output->writeln(sprintf('  - %s', $detail));
            }
        }

        return $result->errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
