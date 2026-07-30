<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Console;

use App\Expense\Application\Command\CheckOverdueCashAdvances\CheckOverdueCashAdvancesCommand as CheckOverdueCashAdvancesMessage;
use App\Expense\Application\Command\CheckOverdueCashAdvances\CheckOverdueCashAdvancesHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'expense:check-overdue-advances',
    description: 'Marca adelantos vencidos (>3 días) como pendientes de revisión.',
)]
final class CheckOverdueCashAdvancesCommand extends Command
{
    public function __construct(
        private readonly CheckOverdueCashAdvancesHandler $handler,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ($this->handler)(new CheckOverdueCashAdvancesMessage());

        $output->writeln('Adelantos vencidos verificados correctamente.');

        return Command::SUCCESS;
    }
}
