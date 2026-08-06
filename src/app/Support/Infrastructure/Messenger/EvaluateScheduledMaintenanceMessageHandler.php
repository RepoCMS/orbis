<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Messenger;

use App\Support\Application\Command\EvaluateScheduledMaintenance\EvaluateScheduledMaintenanceCommand;
use App\Support\Application\Command\EvaluateScheduledMaintenance\EvaluateScheduledMaintenanceHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class EvaluateScheduledMaintenanceMessageHandler
{
    public function __construct(
        private EvaluateScheduledMaintenanceHandler $handler,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(EvaluateScheduledMaintenanceMessage $message): void
    {
        $result = ($this->handler)(new EvaluateScheduledMaintenanceCommand());

        $this->logger->info('Scheduled maintenance evaluation: {created} tickets created, {alerts} alerts sent, {due} overdue, {upcoming} upcoming', [
            'created' => $result->ticketsCreated,
            'alerts' => $result->alertsSent,
            'due' => $result->dueMaintenances,
            'upcoming' => $result->upcomingAlerts,
        ]);
    }
}
