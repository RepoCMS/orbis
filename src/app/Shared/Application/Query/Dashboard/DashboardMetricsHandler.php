<?php

declare(strict_types=1);

namespace App\Shared\Application\Query\Dashboard;

use Doctrine\DBAL\Connection;

/**
 * Recopila métricas operacionales para el dashboard principal.
 * Usa DBAL nativo para evitar cargar entidades completas solo para conteos.
 */
final readonly class DashboardMetricsHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function __invoke(): DashboardMetrics
    {
        $today      = (new \DateTimeImmutable())->format('Y-m-d');
        $yesterday  = (new \DateTimeImmutable('-1 day'))->format('Y-m-d');
        $monthStart = (new \DateTimeImmutable('first day of this month'))->format('Y-m-d');

        return new DashboardMetrics(
            ticketsOpenTotal:            $this->countTicketsOpen(),
            ticketsCreatedToday:         $this->countTicketsCreatedToday($today),
            ticketsCreatedYesterday:     $this->countTicketsCreatedToday($yesterday),
            ticketsByServiceType:        $this->ticketsByServiceType(),
            ticketsClosedThisMonth:      $this->countTicketsClosedThisMonth($monthStart),
            repairsInProgress:           $this->countRepairsInProgress(),
            repairsCompletedToday:       $this->countRepairsCompletedOn($today),
            repairsCompletedYesterday:   $this->countRepairsCompletedOn($yesterday),
            stockMovementsToday:         $this->countStockMovementsToday($today),
            stockMovementsYesterday:     $this->countStockMovementsToday($yesterday),
            stockAlerts:                 $this->getStockAlerts(),
            recentTickets:               $this->getRecentTickets(),
        );
    }

    private function countTicketsOpen(): int
    {
        $sql = "SELECT COUNT(*) FROM tickets WHERE status NOT IN ('closed','cancelled') AND deleted_at IS NULL";

        return (int) $this->connection->fetchOne($sql);
    }

    private function countTicketsCreatedToday(string $date): int
    {
        // Tickets are identified by ULIDs (no explicit created_at); use scheduled_at as proxy
        $sql = 'SELECT COUNT(*) FROM tickets WHERE DATE(scheduled_at) = :d AND deleted_at IS NULL';
        $result = $this->connection->fetchOne($sql, ['d' => $date]);

        return (int) ($result ?? 0);
    }

    private function ticketsByServiceType(): array
    {
        $sql = "
            SELECT service_type, COUNT(*) as total
            FROM tickets
            WHERE status NOT IN ('closed','cancelled') AND deleted_at IS NULL
            GROUP BY service_type
            ORDER BY total DESC
        ";

        return $this->connection->fetchAllAssociative($sql);
    }

    private function countTicketsClosedThisMonth(string $monthStart): int
    {
        $sql = "SELECT COUNT(*) FROM tickets WHERE status = 'closed' AND DATE(closed_at) >= :d AND deleted_at IS NULL";
        $result = $this->connection->fetchOne($sql, ['d' => $monthStart]);

        return (int) ($result ?? 0);
    }

    private function countRepairsInProgress(): int
    {
        $sql = "SELECT COUNT(*) FROM repairs WHERE status IN ('pending', 'in_progress')";
        $result = $this->connection->fetchOne($sql);

        return (int) ($result ?? 0);
    }

    private function countRepairsCompletedOn(string $date): int
    {
        $sql = "SELECT COUNT(*) FROM repairs WHERE status = 'completed' AND DATE(completed_at) = :d";
        $result = $this->connection->fetchOne($sql, ['d' => $date]);

        return (int) ($result ?? 0);
    }

    private function countStockMovementsToday(string $date): int
    {
        $sql = 'SELECT COUNT(*) FROM stock_movements WHERE DATE(created_at) = :d';
        $result = $this->connection->fetchOne($sql, ['d' => $date]);

        return (int) ($result ?? 0);
    }

    /** @return array<array{productName: string, code: string, locationName: string, current: int, minimum: int}> */
    private function getStockAlerts(): array
    {
        // Stock items below minimum threshold
        $sql = "
            SELECT
                p.name       AS productName,
                p.code,
                l.name       AS locationName,
                sm.alert_threshold,
                COALESCE(ns.quantity, 0) AS current
            FROM stock_minimums sm
            INNER JOIN products p ON p.id = sm.product_id AND p.deleted_at IS NULL
            INNER JOIN stock_locations l ON l.id = sm.location_id AND l.deleted_at IS NULL
            LEFT JOIN nonserialized_stock ns ON ns.product_id = sm.product_id
                AND ns.location_id = sm.location_id
                AND ns.status = 'disponible'
                AND ns.deleted_at IS NULL
            WHERE COALESCE(ns.quantity, 0) < sm.alert_threshold
            ORDER BY (sm.alert_threshold - COALESCE(ns.quantity, 0)) DESC
            LIMIT 5
        ";

        return $this->connection->fetchAllAssociative($sql);
    }

    /** @return array<array{id: string, ticketNumber: string, status: string, serviceType: string, scheduledAt: string|null}> */
    private function getRecentTickets(): array
    {
        $sql = '
            SELECT
                t.id,
                t.ticket_number AS ticketNumber,
                t.status,
                t.service_type  AS serviceType,
                t.scheduled_at  AS scheduledAt
            FROM tickets t
            WHERE t.deleted_at IS NULL
            ORDER BY t.id DESC
            LIMIT 10
        ';

        return $this->connection->fetchAllAssociative($sql);
    }
}
