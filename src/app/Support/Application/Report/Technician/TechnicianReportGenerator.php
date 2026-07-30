<?php

declare(strict_types=1);

namespace App\Support\Application\Report\Technician;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class TechnicianReportGenerator implements ReportGeneratorInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function getTitle(): string
    {
        return 'Reporte de Técnicos';
    }

    public function getHeaders(): array
    {
        return [
            'Técnico',
            'Identificación',
            'Perfil',
            'Tickets Completados',
            'Tiempo Promedio (horas)',
            'SLA Cumplidos',
            'SLA Incumplidos',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['e.deleted_at IS NULL', 't.deleted_at IS NULL'];

        if (!empty($filters['date_from'])) {
            $conditions[] = 't.closed_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 't.closed_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                CONCAT(e.first_name, ' ', e.last_name) AS technician_name,
                e.identification_number,
                e.profile,
                COUNT(DISTINCT t.id) AS total_assignments,
                SUM(CASE WHEN t.status = 'closed' THEN 1 ELSE 0 END) AS tickets_completed,
                COALESCE(
                    AVG(
                        TIMESTAMPDIFF(SECOND, t.assistance_start_at, t.closed_at)
                    ) / 3600,
                    0
                ) AS avg_hours,
                SUM(CASE
                    WHEN t.status = 'closed'
                         AND (t.sla_deadline_at IS NULL OR t.closed_at <= t.sla_deadline_at)
                    THEN 1 ELSE 0
                END) AS sla_met,
                SUM(CASE
                    WHEN t.status = 'closed'
                         AND t.sla_deadline_at IS NOT NULL
                         AND t.closed_at > t.sla_deadline_at
                    THEN 1 ELSE 0
                END) AS sla_breached
            FROM org_employees e
            INNER JOIN support_ticket_assignments ta ON ta.employee_id = e.id
            INNER JOIN support_tickets t ON t.id = ta.ticket_id AND t.deleted_at IS NULL
            WHERE e.profile IN ('technician_field', 'supervisor_technician')
              AND {$where}
            GROUP BY e.id, e.first_name, e.last_name, e.identification_number, e.profile
            HAVING tickets_completed > 0
            ORDER BY tickets_completed DESC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'Técnico'                => $row['technician_name'],
                'Identificación'         => $row['identification_number'],
                'Perfil'                 => $row['profile'],
                'Tickets Completados'    => (int) $row['tickets_completed'],
                'Tiempo Promedio (horas)' => round((float) $row['avg_hours'], 2),
                'SLA Cumplidos'          => (int) $row['sla_met'],
                'SLA Incumplidos'        => (int) $row['sla_breached'],
            ];
        }

        return $result;
    }
}
