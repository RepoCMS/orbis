<?php

declare(strict_types=1);

namespace App\Support\Application\Report\TicketsByClient;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class TicketsByClientReportGenerator implements ReportGeneratorInterface
{
    private const SERVICE_TYPE_LABELS = [
        'preventive'            => 'Mantenimiento Preventivo',
        'corrective'            => 'Mantenimiento Correctivo',
        'emergency'             => 'Emergencia',
        'installation'          => 'Instalación',
        'removal'               => 'Retiro',
        'technical_support'     => 'Soporte Técnico',
        'software_update'       => 'Actualización de Software',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getTitle(): string
    {
        return 'Tickets por Cliente';
    }

    public function getHeaders(): array
    {
        return [
            'Cliente',
            'RUC',
            'Total Tickets',
            'Tipo Más Frecuente',
            'Tiempo Promedio Resolución (horas)',
            'Tickets con Brecha SLA',
            '% Cumplimiento SLA',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['t.deleted_at IS NULL'];

        if (!empty($filters['date_from'])) {
            $conditions[] = 't.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 't.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['client_id'])) {
            $conditions[] = 't.client_id = :client_id';
            $params['client_id'] = $filters['client_id'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                t.client_id,
                c.name AS client_name,
                c.ruc,
                COUNT(t.id) AS total_tickets,
                COUNT(DISTINCT t.service_type) AS service_type_count,
                AVG(
                    CASE
                        WHEN t.closed_at IS NOT NULL AND t.created_at IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, t.created_at, t.closed_at)
                        ELSE NULL
                    END
                ) AS avg_resolution_hours,
                SUM(CASE WHEN t.sla_deadline_at IS NOT NULL AND t.closed_at IS NULL AND t.sla_deadline_at < NOW() AND t.status NOT IN ('closed', 'cancelled') THEN 1 ELSE 0 END) AS sla_breach_open,
                SUM(CASE WHEN t.sla_deadline_at IS NOT NULL AND t.closed_at IS NOT NULL AND t.closed_at > t.sla_deadline_at THEN 1 ELSE 0 END) AS sla_breach_closed,
                SUM(CASE WHEN t.sla_deadline_at IS NOT NULL AND t.closed_at IS NOT NULL AND t.closed_at <= t.sla_deadline_at THEN 1 ELSE 0 END) AS sla_met
            FROM tickets t
            LEFT JOIN client_clients c ON c.id = t.client_id AND c.deleted_at IS NULL
            WHERE {$where}
            GROUP BY t.client_id, c.name, c.ruc
            ORDER BY total_tickets DESC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $totalSla = (int) ($row['sla_breach_open'] ?? 0) + (int) ($row['sla_breach_closed'] ?? 0) + (int) ($row['sla_met'] ?? 0);
            $compliance = $totalSla > 0 ? round(((int) ($row['sla_met'] ?? 0) / $totalSla) * 100, 1) : null;

            $result[] = [
                'Cliente'                    => $row['client_name'] ?? '—',
                'RUC'                        => $row['ruc'] ?? '—',
                'Total Tickets'              => (string) ($row['total_tickets'] ?? 0),
                'Tipo Más Frecuente'         => ($row['service_type_count'] ?? 0) > 0 ? 'Ver detalle' : '—',
                'Tiempo Promedio Resolución (horas)' => $row['avg_resolution_hours'] !== null ? number_format((float) $row['avg_resolution_hours'], 1) : '—',
                'Tickets con Brecha SLA'     => (string) ((int) ($row['sla_breach_open'] ?? 0) + (int) ($row['sla_breach_closed'] ?? 0)),
                '% Cumplimiento SLA'          => $compliance !== null ? $compliance . '%' : '—',
            ];
        }

        return $result;
    }
}
