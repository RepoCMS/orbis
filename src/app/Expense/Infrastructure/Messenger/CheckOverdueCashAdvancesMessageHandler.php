<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Messenger;

use App\Expense\Application\Command\CheckOverdueCashAdvances\CheckOverdueCashAdvancesCommand;
use App\Expense\Application\Command\CheckOverdueCashAdvances\CheckOverdueCashAdvancesHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckOverdueCashAdvancesMessageHandler
{
    public function __construct(
        private CheckOverdueCashAdvancesHandler $handler,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CheckOverdueCashAdvancesMessage $message): void
    {
        ($this->handler)(new CheckOverdueCashAdvancesCommand());

        $this->logger->info('Checked overdue cash advances');
    }
}
