<?php

declare(strict_types=1);

namespace App\Support\Application\Query\SupportDashboardMetrics;

use Doctrine\DBAL\Connection;

final readonly class SupportDashboardMetricsHandler
{
    private const TECHNICIAN_PROFILES = ['technician_field', 'supervisor_technician'];

    public function __construct(
        private Connection $dbal,
    ) {
    }

    public function __invoke(): SupportDashboardMetrics
    {
        $metrics = new SupportDashboardMetrics();

        $this->loadStatusBreakdown($metrics);
        $this->loadSlaMetrics($metrics);
        $this->loadCompletionMetrics($metrics);
        $this->loadReopenAndCancelMetrics($metrics);
        $this->loadTopTechnicians($metrics);
        $this->loadTopClients($metrics);
        $this->loadCompletionsTrend($metrics);
        $this->loadRecentTickets($metrics);
        $this->loadTechniciansAvailable($metrics);
        $this->loadTechniciansBusy($metrics);
        $this->loadAvgResolution($metrics);
        $this->loadAvgTimeByTechnician($metrics);

        return $metrics;
    }

    private function loadStatusBreakdown(SupportDashboardMetrics $m): void
    {
        $rows = $this->dbal->fetchAllAssociative(
            'SELECT status, COUNT(*) AS cnt FROM support_tickets WHERE deleted_at IS NULL GROUP BY status'
        );

        $m->totalTickets = 0;
        foreach ($rows as $row) {
            $cnt = (int) $row['cnt'];
            $m->totalTickets += $cnt;
            $m->statusBreakdown[] = $row;

            switch ($row['status']) {
                case 'created':
                    $m->openTickets += $cnt;
                    break;
                case 'assigned':
                    $m->assignedTickets += $cnt;
                    break;
                case 'in_transit_out':
                case 'waiting':
                case 'in_assistance':
                    $m->inProgressTickets += $cnt;
                    break;
                case 'pending_closure':
                    $m->pendingClosure += $cnt;
                    break;
            }
        }
    }

    private function loadSlaMetrics(SupportDashboardMetrics $m): void
    {
        $m->overdueSla = (int) $this->dbal->fetchOne(
            "SELECT COUNT(*) FROM support_tickets
             WHERE deleted_at IS NULL
               AND sla_deadline_at < NOW()
               AND status NOT IN ('closed', 'cancelled')"
        );

        $totalSla = (int) $this->dbal->fetchOne(
            'SELECT COUNT(*) FROM support_tickets
             WHERE deleted_at IS NULL
               AND sla_deadline_at IS NOT NULL
               AND closed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
        );

        $m->slaBreachCount30d = (int) $this->dbal->fetchOne(
            'SELECT COUNT(*) FROM support_tickets
             WHERE deleted_at IS NULL
               AND sla_deadline_at IS NOT NULL
               AND sla_deadline_at < closed_at
               AND closed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
        );

        $m->slaBreachRate30d = $totalSla > 0
            ? round(($m->slaBreachCount30d / $totalSla) * 100, 1)
            : 0.0;
    }

    private function loadCompletionMetrics(SupportDashboardMetrics $m): void
    {
        $m->completedToday = (int) $this->dbal->fetchOne(
            'SELECT COUNT(*) FROM support_tickets
             WHERE deleted_at IS NULL
               AND closed_at >= CURDATE()
               AND closed_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)'
        );

        $m->completedThisWeek = (int) $this->dbal->fetchOne(
            'SELECT COUNT(*) FROM support_tickets
             WHERE deleted_at IS NULL
               AND closed_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)'
        );
    }

    private function loadAvgResolution(SupportDashboardMetrics $m): void
    {
        $avg = $this->dbal->fetchOne(
            'SELECT AVG(TIMESTAMPDIFF(HOUR,
                COALESCE(scheduled_at, assistance_start_at, in_transit_out_at),
                COALESCE(closed_at, cancelled_at)
             )) FROM support_tickets
             WHERE deleted_at IS NULL
               AND (closed_at IS NOT NULL OR cancelled_at IS NOT NULL)
               AND (scheduled_at IS NOT NULL OR assistance_start_at IS NOT NULL OR in_transit_out_at IS NOT NULL)'
        );
        $m->avgResolutionHours = $avg !== false && $avg !== null ? round((float) $avg, 1) : 0.0;

        $byType = $this->dbal->fetchAllAssociative(
            'SELECT service_type,
                    AVG(TIMESTAMPDIFF(HOUR,
                        COALESCE(scheduled_at, assistance_start_at, in_transit_out_at),
                        COALESCE(closed_at, cancelled_at)
                    )) AS avg_hours,
                    COUNT(*) AS cnt
             FROM support_tickets
             WHERE deleted_at IS NULL
               AND (closed_at IS NOT NULL OR cancelled_at IS NOT NULL)
               AND (scheduled_at IS NOT NULL OR assistance_start_at IS NOT NULL OR in_transit_out_at IS NOT NULL)
             GROUP BY service_type
             ORDER BY avg_hours DESC'
        );
        $m->avgResolutionByServiceType = $byType;
    }

    private function loadAvgTimeByTechnician(SupportDashboardMetrics $m): void
    {
        $m->avgTimeByTechnician = $this->dbal->fetchAllAssociative(
            'SELECT ta.employee_id,
                    AVG(TIMESTAMPDIFF(HOUR,
                        COALESCE(t.scheduled_at, t.assistance_start_at, t.in_transit_out_at),
                        COALESCE(t.closed_at, t.cancelled_at)
                    )) AS avg_hours,
                    COUNT(*) AS cnt
             FROM support_ticket_assignments ta
             JOIN support_tickets t ON t.id = ta.ticket_id AND t.deleted_at IS NULL
             WHERE t.deleted_at IS NULL
               AND (t.closed_at IS NOT NULL OR t.cancelled_at IS NOT NULL)
               AND (t.scheduled_at IS NOT NULL OR t.assistance_start_at IS NOT NULL OR t.in_transit_out_at IS NOT NULL)
             GROUP BY ta.employee_id
             ORDER BY avg_hours DESC
             LIMIT 10'
        );
    }

    private function loadReopenAndCancelMetrics(SupportDashboardMetrics $m): void
    {
        $m->reopenedCount = (int) $this->dbal->fetchOne(
            'SELECT COUNT(*) FROM support_tickets WHERE deleted_at IS NULL AND reopened_at IS NOT NULL'
        );

        $totalTerminal = (int) $this->dbal->fetchOne(
            'SELECT COUNT(*) FROM support_tickets WHERE deleted_at IS NULL AND (closed_at IS NOT NULL OR cancelled_at IS NOT NULL)'
        );

        $m->cancelledCount = (int) $this->dbal->fetchOne(
            'SELECT COUNT(*) FROM support_tickets WHERE deleted_at IS NULL AND cancelled_at IS NOT NULL'
        );

        $m->reopenRate = $totalTerminal > 0
            ? round(($m->reopenedCount / $totalTerminal) * 100, 1)
            : 0.0;

        $m->cancellationRate = $totalTerminal > 0
            ? round(($m->cancelledCount / $totalTerminal) * 100, 1)
            : 0.0;
    }

    private function loadTopTechnicians(SupportDashboardMetrics $m): void
    {
        $m->topTechnicians = $this->dbal->fetchAllAssociative(
            'SELECT ta.employee_id, COUNT(*) AS cnt
             FROM support_ticket_assignments ta
             JOIN support_tickets t ON t.id = ta.ticket_id AND t.deleted_at IS NULL
             GROUP BY ta.employee_id
             ORDER BY cnt DESC
             LIMIT 5'
        );
    }

    private function loadTopClients(SupportDashboardMetrics $m): void
    {
        $m->topClientsByTickets = $this->dbal->fetchAllAssociative(
            'SELECT client_id, COUNT(*) AS cnt
             FROM support_tickets
             WHERE deleted_at IS NULL
             GROUP BY client_id
             ORDER BY cnt DESC
             LIMIT 10'
        );
    }

    private function loadCompletionsTrend(SupportDashboardMetrics $m): void
    {
        $m->completionsTrend = $this->dbal->fetchAllAssociative(
            'SELECT DATE(closed_at) AS day, COUNT(*) AS cnt
             FROM support_tickets
             WHERE deleted_at IS NULL
               AND closed_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(closed_at)
             ORDER BY day ASC'
        );
    }

    private function loadRecentTickets(SupportDashboardMetrics $m): void
    {
        $m->recentTickets = $this->dbal->fetchAllAssociative(
            'SELECT id, ticket_number, client_id, service_type, status,
                    scheduled_at, sla_deadline_at, closed_at, cancelled_at,
                    created_by
             FROM support_tickets
             WHERE deleted_at IS NULL
             ORDER BY id DESC
             LIMIT 10'
        );
    }

    private function loadTechniciansAvailable(SupportDashboardMetrics $m): void
    {
        $profiles = implode("','", self::TECHNICIAN_PROFILES);
        $m->techniciansAvailable = (int) $this->dbal->fetchOne(
            "SELECT COUNT(*) FROM employees
             WHERE status = 'active'
               AND profile IN ('{$profiles}')
               AND deleted_at IS NULL"
        );
    }

    private function loadTechniciansBusy(SupportDashboardMetrics $m): void
    {
        $m->techniciansBusy = (int) $this->dbal->fetchOne(
            "SELECT COUNT(DISTINCT ta.employee_id)
             FROM support_ticket_assignments ta
             JOIN support_tickets t ON t.id = ta.ticket_id AND t.deleted_at IS NULL
              WHERE t.status IN ('in_transit_out', 'waiting', 'in_assistance')
               AND t.deleted_at IS NULL"
        );
    }
}
