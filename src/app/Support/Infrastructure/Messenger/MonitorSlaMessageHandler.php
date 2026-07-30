<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Messenger;

use App\Support\Application\Command\MonitorSla\MonitorSlaCommand;
use App\Support\Application\Command\MonitorSla\MonitorSlaHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class MonitorSlaMessageHandler
{
    public function __construct(
        private MonitorSlaHandler $handler,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(MonitorSlaMessage $message): void
    {
        $result = ($this->handler)(new MonitorSlaCommand());

        if ($result->breachedAlerts > 0 || $result->criticalAlerts > 0 || $result->aboutToExpireAlerts > 0) {
            $this->logger->info('SLA monitor completed: {total} tickets checked, {about} about to expire, {critical} critical, {breached} breached', [
                'total' => $result->totalTickets,
                'about' => $result->aboutToExpireAlerts,
                'critical' => $result->criticalAlerts,
                'breached' => $result->breachedAlerts,
            ]);
        }
    }
}
