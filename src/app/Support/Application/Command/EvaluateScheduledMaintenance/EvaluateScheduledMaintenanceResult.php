<?php

declare(strict_types=1);

namespace App\Support\Application\Command\EvaluateScheduledMaintenance;

final readonly class EvaluateScheduledMaintenanceResult
{
    public function __construct(
        public int $ticketsCreated,
        public int $alertsSent,
        public int $dueMaintenances,
        public int $upcomingAlerts,
    ) {
    }
}
