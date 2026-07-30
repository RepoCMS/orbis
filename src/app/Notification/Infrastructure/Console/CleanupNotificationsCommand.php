<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Console;

use App\Notification\Application\Command\CleanupOldNotifications\CleanupOldNotificationsCommand;
use App\Notification\Application\Command\CleanupOldNotifications\CleanupOldNotificationsHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'orbis:notifications:cleanup',
    description: 'Purga registros de notificaciones mayores a 3 meses.',
)]
final class CleanupNotificationsCommand extends Command
{
    public function __construct(
        private readonly CleanupOldNotificationsHandler $handler,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->handler->__invoke(new CleanupOldNotificationsCommand());

        $io->success(sprintf('Se purgaron %d registros de notificaciones.', $count));

        return Command::SUCCESS;
    }
}
