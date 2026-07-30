<?php

declare(strict_types=1);

namespace App\Support\Application\Query\SupportDashboardMetrics;

final class SupportDashboardMetrics
{
    public function __construct(
        public int $openTickets = 0,
        public int $assignedTickets = 0,
        public int $inProgressTickets = 0,
        public int $pendingClosure = 0,
        public int $overdueSla = 0,
        public int $completedToday = 0,
        public int $completedThisWeek = 0,
        public float $avgResolutionHours = 0.0,
        public int $slaBreachCount30d = 0,
        public float $slaBreachRate30d = 0.0,
        public int $reopenedCount = 0,
        public float $reopenRate = 0.0,
        public int $cancelledCount = 0,
        public float $cancellationRate = 0.0,
        public int $totalTickets = 0,
        public int $techniciansAvailable = 0,
        public int $techniciansBusy = 0,
        public array $statusBreakdown = [],
        public array $avgResolutionByServiceType = [],
        public array $topTechnicians = [],
        public array $topClientsByTickets = [],
        public array $completionsTrend = [],
        public array $recentTickets = [],
        public array $avgTimeByTechnician = [],
    ) {}
}
