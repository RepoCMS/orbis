<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Console;

use App\Inventory\Application\Command\ImportSerializedStock\ImportSerializedStockCommand;
use App\Inventory\Application\Command\ImportSerializedStock\ImportSerializedStockHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'inventory:import:serialized-stock',
    description: 'Importa stock serializado desde un archivo CSV.',
)]
final class InventoryImportSerializedStockCliCommand extends Command
{
    public function __construct(
        private readonly ImportSerializedStockHandler $handler,
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

        $output->writeln(sprintf('Importando stock serializado desde: %s', $filePath));

        $result = ($this->handler)(new ImportSerializedStockCommand(
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
