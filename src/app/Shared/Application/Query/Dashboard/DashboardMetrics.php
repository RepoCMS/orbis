<?php

declare(strict_types=1);

namespace App\Shared\Application\Query\Dashboard;

final class DashboardMetrics
{
    /**
     * @param array<array{service_type: string, total: int}>     $ticketsByServiceType
     * @param array<array{productName: string, code: string, locationName: string, current: int, alert_threshold: int}> $stockAlerts
     * @param array<array{id: string, ticketNumber: string, status: string, serviceType: string, scheduledAt: string|null}> $recentTickets
     */
    public function __construct(
        public readonly int   $ticketsOpenTotal,
        public readonly int   $ticketsCreatedToday,
        public readonly int   $ticketsCreatedYesterday,
        public readonly array $ticketsByServiceType,
        public readonly int   $ticketsClosedThisMonth,
        public readonly int   $repairsInProgress,
        public readonly int   $repairsCompletedToday,
        public readonly int   $repairsCompletedYesterday,
        public readonly int   $stockMovementsToday,
        public readonly int   $stockMovementsYesterday,
        public readonly array $stockAlerts,
        public readonly array $recentTickets,
    ) {}

    public function ticketsTodayVsYesterdayDiff(): int
    {
        return $this->ticketsCreatedToday - $this->ticketsCreatedYesterday;
    }

    public function repairsVsYesterdayDiff(): int
    {
        return $this->repairsCompletedToday - $this->repairsCompletedYesterday;
    }

    public function movementsVsYesterdayDiff(): int
    {
        return $this->stockMovementsToday - $this->stockMovementsYesterday;
    }
}
