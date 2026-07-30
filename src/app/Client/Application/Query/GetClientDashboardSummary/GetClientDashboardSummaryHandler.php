<?php

declare(strict_types=1);

namespace App\Client\Application\Query\GetClientDashboardSummary;

use Doctrine\DBAL\Connection;

/**
 * Recopila métricas operacionales para el dashboard de Clientes.
 * Usa DBAL nativo para evitar cargar entidades completas solo para conteos.
 */
final readonly class GetClientDashboardSummaryHandler
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function __invoke(GetClientDashboardSummaryQuery $query): ClientDashboardSummary
    {
        return new ClientDashboardSummary(
            totalClients:          $this->countTotalClients(),
            activeContacts:        $this->countActiveContacts(),
            totalTerminals:        $this->countTotalTerminals(),
            terminalsByStatus:     $this->terminalsByStatus(),
            recentClients:         $this->getRecentClients(),
            clientsByStatus:       $this->clientsByStatus(),
            contractsExpiring:     $this->contractsExpiring(),
            topClientsByTerminals: $this->topClientsByTerminals(),
            contactsByStatus:      $this->contactsByStatus(),
            unassignedTerminals:   $this->countUnassignedTerminals(),
            monthlyGrowth:         $this->monthlyGrowth(),
        );
    }

    private function countTotalClients(): int
    {
        $sql = "SELECT COUNT(*) FROM client_clients WHERE deleted_at IS NULL";
        return (int) $this->connection->fetchOne($sql);
    }

    private function countActiveContacts(): int
    {
        $sql = "SELECT COUNT(*) FROM contacts WHERE status = 'active' AND deleted_at IS NULL";
        return (int) $this->connection->fetchOne($sql);
    }

    private function countTotalTerminals(): int
    {
        $sql = "SELECT COUNT(*) FROM client_terminals WHERE deleted_at IS NULL";
        return (int) $this->connection->fetchOne($sql);
    }

    /** @return array<array{status: string, label: string, count: int, color: string}> */
    private function terminalsByStatus(): array
    {
        $sql = "
            SELECT status, COUNT(*) as total
            FROM client_terminals
            WHERE deleted_at IS NULL
            GROUP BY status
            ORDER BY total DESC
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        $colorMap = [
            'active'   => '#059669',
            'inactive' => '#6b7280',
        ];

        $labelMap = [
            'active'   => 'Activos',
            'inactive' => 'Inactivos',
        ];

        $result = [];
        foreach ($rows as $row) {
            $status = (string) $row['status'];
            $result[] = [
                'status' => $status,
                'label'  => $labelMap[$status] ?? $status,
                'count'  => (int) $row['total'],
                'color'  => $colorMap[$status] ?? '#6b7280',
            ];
        }

        return $result;
    }

    /** @return array<array{id: string, name: string, ruc: string, status: string}> */
    private function getRecentClients(): array
    {
        $sql = "
            SELECT
                id,
                name,
                ruc,
                status
            FROM client_clients
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT 5
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        return array_map(
            fn (array $row): array => [
                'id'     => (string) $row['id'],
                'name'   => (string) $row['name'],
                'ruc'    => (string) $row['ruc'],
                'status' => (string) $row['status'],
            ],
            $rows
        );
    }

    // ─── Nuevos KPIs ────────────────────────────────────────────────

    /** @return array<array{status: string, label: string, count: int, color: string}> */
    private function clientsByStatus(): array
    {
        $sql = "
            SELECT status, COUNT(*) as total
            FROM client_clients
            WHERE deleted_at IS NULL
            GROUP BY status
            ORDER BY total DESC
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        $colorMap = [
            'active'   => '#059669',
            'inactive' => '#6b7280',
        ];
        $labelMap = [
            'active'   => 'Activos',
            'inactive' => 'Inactivos',
        ];

        $result = [];
        foreach ($rows as $row) {
            $status = (string) $row['status'];
            $result[] = [
                'status' => $status,
                'label'  => $labelMap[$status] ?? $status,
                'count'  => (int) $row['total'],
                'color'  => $colorMap[$status] ?? '#6b7280',
            ];
        }

        return $result;
    }

    /** @return array<array{label: string, range: string, count: int}> */
    private function contractsExpiring(): array
    {
        $sql = "
            SELECT
                CASE
                    WHEN end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN '30'
                    WHEN end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 60 DAY) THEN '60'
                    WHEN end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 90 DAY) THEN '90'
                END AS `range`,
                COUNT(*) as total
            FROM client_contracts
            WHERE end_date >= NOW()
              AND status = 'active'
            GROUP BY `range`
            ORDER BY `range` ASC
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        $labelMap = [
            '30' => 'Próximos 30 días',
            '60' => 'Próximos 60 días',
            '90' => 'Próximos 90 días',
        ];

        $result = [];
        foreach ($rows as $row) {
            $range = (string) $row['range'];
            $result[] = [
                'label' => $labelMap[$range] ?? $range,
                'range' => $range,
                'count' => (int) $row['total'],
            ];
        }

        return $result;
    }

    /** @return array<array{clientId: string, clientName: string, terminalCount: int}> */
    private function topClientsByTerminals(): array
    {
        $sql = "
            SELECT
                c.id AS clientId,
                c.name AS clientName,
                COUNT(t.id) AS terminalCount
            FROM client_clients c
            LEFT JOIN client_terminals t ON c.id = t.client_id AND t.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
            GROUP BY c.id, c.name
            ORDER BY terminalCount DESC
            LIMIT 10
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        return array_map(
            fn (array $row): array => [
                'clientId'      => (string) $row['clientId'],
                'clientName'    => (string) $row['clientName'],
                'terminalCount' => (int) $row['terminalCount'],
            ],
            $rows
        );
    }

    /** @return array<array{status: string, label: string, count: int}> */
    private function contactsByStatus(): array
    {
        $sql = "
            SELECT status, COUNT(*) as total
            FROM contacts
            WHERE deleted_at IS NULL
            GROUP BY status
            ORDER BY total DESC
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        $labelMap = [
            'active'   => 'Activos',
            'inactive' => 'Inactivos',
        ];

        $result = [];
        foreach ($rows as $row) {
            $status = (string) $row['status'];
            $result[] = [
                'status' => $status,
                'label'  => $labelMap[$status] ?? $status,
                'count'  => (int) $row['total'],
            ];
        }

        return $result;
    }

    private function countUnassignedTerminals(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM client_terminals t
            WHERE t.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM client_terminal_assignments ta
                  WHERE ta.terminal_id = t.id AND ta.deleted_at IS NULL
              )
        ";
        return (int) $this->connection->fetchOne($sql);
    }

    /** @return array<array{month: string, year: int, count: int}> */
    private function monthlyGrowth(): array
    {
        $sql = "
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') AS month,
                YEAR(created_at) AS year,
                COUNT(*) AS count
            FROM client_clients
            WHERE deleted_at IS NULL
              AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month, year
            ORDER BY year ASC, month ASC
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        return array_map(
            fn (array $row): array => [
                'month' => (string) $row['month'],
                'year'  => (int) $row['year'],
                'count' => (int) $row['count'],
            ],
            $rows
        );
    }
}
