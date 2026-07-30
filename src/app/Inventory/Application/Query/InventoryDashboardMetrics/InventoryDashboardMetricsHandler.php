<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\InventoryDashboardMetrics;

use Doctrine\DBAL\Connection;

final readonly class InventoryDashboardMetricsHandler
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function __invoke(): InventoryDashboardMetrics
    {
        $totalValue = $this->totalInventoryValue();
        $abc = $this->abcAnalysis();
        $fillRate = $this->fillRate();

        return new InventoryDashboardMetrics(
            totalInventoryValue: $totalValue,
            totalProducts: $this->countProducts(),
            totalSerializedStock: $this->countSerializedStock(),
            totalNonSerializedStock: $this->countNonSerializedStock(),
            deadStockCount: $this->countDeadStock(180),
            itemsWithoutMovement90d: $this->countSerializedWithoutMovement(90),
            fillRateItems: $fillRate['total_items'] ?? 0,
            fillRatePct: $fillRate['fill_rate'] ?? 0.0,
            pendingRequests: $this->countPendingRequests(),
            openRepairs: $this->countOpenRepairs(),
            openPurchaseOrders: $this->countOpenPurchaseOrders(),
            physicalCountsPending: $this->countPendingPhysicalCounts(),
            pendingReviewCount: $this->countPendingReview(),
            stockAlertsCount: $this->countStockAlerts(),
            avgRepairTimeHours: $this->avgRepairTime(),
            movementsToday: $this->countMovementsToday(),
            abcAnalysis: $abc,
            deadStock90d: $this->deadStock(90, 10),
            fillRate: [$fillRate],
            valueByCategory: $this->valueByProductType(),
            movementsByType: $this->movementsByType(30),
            stockByLocation: $this->stockByLocation(),
            stockAlerts: $this->stockAlerts(),
            movementsByTypeToday: $this->movementsByTypeToday(),
            topUsedParts: $this->topUsedParts(),
        );
    }

    private function totalInventoryValue(): float
    {
        $sql = '
            SELECT COALESCE(
                (SELECT COALESCE(SUM(p.last_purchase_price), 0)
                 FROM serialized_stock ss
                 INNER JOIN products p ON p.id = ss.product_id
                 WHERE ss.status = :avail AND ss.deleted_at IS NULL)
                +
                (SELECT COALESCE(SUM(p.last_purchase_price * ns.quantity), 0)
                 FROM nonserialized_stock ns
                 INNER JOIN products p ON p.id = ns.product_id
                 WHERE ns.status = :avail2 AND ns.deleted_at IS NULL)
            , 0) AS total
        ';
        return (float) $this->connection->fetchOne($sql, [
            'avail' => 'available',
            'avail2' => 'available',
        ]);
    }

    private function countProducts(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL');
    }

    private function countSerializedStock(): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM serialized_stock WHERE deleted_at IS NULL',
        );
    }

    private function countNonSerializedStock(): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM nonserialized_stock WHERE deleted_at IS NULL',
        );
    }

    private function countDeadStock(int $days): int
    {
        $sql = '
            SELECT COUNT(*) FROM serialized_stock ss
            WHERE ss.status = :avail
              AND ss.deleted_at IS NULL
              AND ss.created_at IS NOT NULL
              AND ss.created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
              AND ss.id NOT IN (
                  SELECT m.serialized_stock_id FROM stock_movements m
                  WHERE m.completed_at >= DATE_SUB(NOW(), INTERVAL :days2 DAY)
                    AND m.serialized_stock_id IS NOT NULL
              )
        ';
        return (int) $this->connection->fetchOne($sql, [
            'avail' => 'available',
            'days' => $days,
            'days2' => $days,
        ]);
    }

    private function countSerializedWithoutMovement(int $days): int
    {
        $sql = '
            SELECT COUNT(*) FROM serialized_stock ss
            WHERE ss.status = :avail AND ss.deleted_at IS NULL
              AND ss.id NOT IN (
                  SELECT m.serialized_stock_id FROM stock_movements m
                  WHERE m.completed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                    AND m.serialized_stock_id IS NOT NULL
              )
        ';
        return (int) $this->connection->fetchOne($sql, [
            'avail' => 'available',
            'days' => $days,
        ]);
    }

    /** @return array{total_items: int, fulfilled: int, fill_rate: float} */
    private function fillRate(): array
    {
        $sql = '
            SELECT
                COUNT(*) AS total_items,
                SUM(CASE WHEN status = :fulfilled THEN 1 ELSE 0 END) AS fulfilled
            FROM stock_request_items
        ';
        $result = $this->connection->fetchAssociative($sql, ['fulfilled' => 'fulfilled']);

        $total = (int) ($result['total_items'] ?? 0);
        $fulfilled = (int) ($result['fulfilled'] ?? 0);

        return [
            'total_items' => $total,
            'fulfilled' => $fulfilled,
            'fill_rate' => $total > 0 ? round(($fulfilled / $total) * 100, 1) : 0.0,
        ];
    }

    private function countPendingRequests(): int
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM stock_requests WHERE status IN ('pending', 'approved', 'awaiting_technician_acceptance')",
        );
    }

    private function countOpenRepairs(): int
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM repairs WHERE status IN ('pending', 'in_progress')",
        );
    }

    private function countOpenPurchaseOrders(): int
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM purchase_orders WHERE status IN ('open', 'partial')",
        );
    }

    private function countPendingPhysicalCounts(): int
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM stock_physical_counts WHERE status = 'pending_approval'",
        );
    }

    /** @return array<array{product: string, code: string, total_value: float, abc_class: string}> */
    private function abcAnalysis(): array
    {
        $sql = '
            WITH product_consumption AS (
                SELECT
                    p.id,
                    p.name AS product,
                    p.code,
                    COALESCE(SUM(m.quantity * p.last_purchase_price), 0) AS total_value
                FROM products p
                LEFT JOIN stock_movements m ON m.product_id = p.id
                    AND m.type IN (:exit_type) AND m.status = :completed
                    AND m.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                WHERE p.deleted_at IS NULL
                GROUP BY p.id, p.name, p.code
            )
            SELECT
                product,
                code,
                total_value,
                CASE
                    WHEN total_value = 0 THEN \'C\'
                    ELSE ELT(NTILE(3) OVER (ORDER BY total_value DESC), \'A\', \'B\', \'C\')
                END AS abc_class
            FROM product_consumption
            ORDER BY total_value DESC
            LIMIT 20
        ';
        $result = $this->connection->fetchAllAssociative($sql, [
            'exit_type' => 'exit',
            'completed' => 'completed',
        ]);

        return array_map(static function (array $row): array {
            $row['abc_class'] = match ($row['abc_class'] ?? 'C') {
                'A' => 'A',
                'B' => 'B',
                default => 'C',
            };
            return $row;
        }, $result);
    }

    /** @return array<array{product_name: string, code: string, serial: string, days_in_stock: int}> */
    private function deadStock(int $days, int $limit): array
    {
        $sql = '
            SELECT
                p.name AS product_name,
                p.code,
                ss.serial_number AS serial,
                DATEDIFF(NOW(), ss.created_at) AS days_in_stock
            FROM serialized_stock ss
            INNER JOIN products p ON p.id = ss.product_id
            WHERE ss.status = :avail AND ss.deleted_at IS NULL
              AND ss.created_at IS NOT NULL
              AND ss.created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
              AND ss.id NOT IN (
                  SELECT m.serialized_stock_id FROM stock_movements m
                  WHERE m.completed_at >= DATE_SUB(NOW(), INTERVAL :days2 DAY)
                    AND m.serialized_stock_id IS NOT NULL
              )
            ORDER BY ss.created_at ASC
            LIMIT :limit
        ';
        return $this->connection->fetchAllAssociative($sql, [
            'avail' => 'available',
            'days' => $days,
            'days2' => $days,
            'limit' => $limit,
        ], ['limit' => \Doctrine\DBAL\ParameterType::INTEGER]);
    }

    /** @return array<array{category: string, total: float}> */
    private function valueByProductType(): array
    {
        $sql = '
            SELECT
                p.tracking_type AS category,
                COALESCE(SUM(
                    CASE WHEN p.tracking_type = :serial
                        THEN p.last_purchase_price
                        ELSE p.last_purchase_price * ns.quantity
                    END
                ), 0) AS total
            FROM products p
            LEFT JOIN serialized_stock ss ON ss.product_id = p.id AND ss.status = :avail AND ss.deleted_at IS NULL
            LEFT JOIN nonserialized_stock ns ON ns.product_id = p.id AND ns.status = :avail2 AND ns.deleted_at IS NULL
            WHERE p.deleted_at IS NULL
            GROUP BY p.tracking_type
        ';
        return $this->connection->fetchAllAssociative($sql, [
            'serial' => 'serialized',
            'avail' => 'available',
            'avail2' => 'available',
        ]);
    }

    /** @return array<array{type: string, total: int}> */
    private function movementsByType(int $days): array
    {
        $sql = '
            SELECT type, COUNT(*) AS total
            FROM stock_movements
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY type
            ORDER BY total DESC
        ';
        return $this->connection->fetchAllAssociative($sql, ['days' => $days]);
    }

    private function countPendingReview(): int
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM nonserialized_stock WHERE status = 'pending_review' AND deleted_at IS NULL",
        );
    }

    private function countStockAlerts(): int
    {
        $sql = '
            SELECT COUNT(*)
            FROM stock_minimums sm
            LEFT JOIN nonserialized_stock ns ON ns.product_id = sm.product_id
                AND ns.location_id = sm.location_id
                AND ns.status = :avail AND ns.deleted_at IS NULL
            WHERE COALESCE(ns.quantity, 0) < sm.alert_threshold
        ';
        return (int) $this->connection->fetchOne($sql, ['avail' => 'available']);
    }

    private function avgRepairTime(): float
    {
        $sql = "
            SELECT COALESCE(AVG(TIMESTAMPDIFF(HOUR, started_at, completed_at)), 0)
            FROM repairs
            WHERE status = 'completed'
              AND started_at IS NOT NULL
              AND completed_at IS NOT NULL
        ";
        return (float) $this->connection->fetchOne($sql);
    }

    private function countMovementsToday(): int
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM stock_movements WHERE DATE(created_at) = CURDATE()",
        );
    }

    /** @return array<array{location: string, serialized: int, nonserialized: int}> */
    private function stockByLocation(): array
    {
        $sql = '
            SELECT
                l.name AS location,
                COALESCE(ss.serialized, 0) AS serialized,
                COALESCE(ns.nonserialized, 0) AS nonserialized
            FROM stock_locations l
            LEFT JOIN (
                SELECT location_id, COUNT(*) AS serialized
                FROM serialized_stock
                WHERE status = :avail AND deleted_at IS NULL
                GROUP BY location_id
            ) ss ON ss.location_id = l.id
            LEFT JOIN (
                SELECT location_id, SUM(quantity) AS nonserialized
                FROM nonserialized_stock
                WHERE status = :avail2 AND deleted_at IS NULL
                GROUP BY location_id
            ) ns ON ns.location_id = l.id
            WHERE l.deleted_at IS NULL
              AND (COALESCE(ss.serialized, 0) > 0 OR COALESCE(ns.nonserialized, 0) > 0)
            ORDER BY l.name ASC
        ';
        return $this->connection->fetchAllAssociative($sql, [
            'avail' => 'available',
            'avail2' => 'available',
        ]);
    }

    /** @return array<array{product: string, location: string, current: int, threshold: int}> */
    private function stockAlerts(): array
    {
        $sql = '
            SELECT
                p.name AS product,
                l.name AS location,
                COALESCE(ns.quantity, 0) AS current,
                sm.alert_threshold AS threshold
            FROM stock_minimums sm
            INNER JOIN products p ON p.id = sm.product_id
            INNER JOIN stock_locations l ON l.id = sm.location_id
            LEFT JOIN nonserialized_stock ns ON ns.product_id = sm.product_id
                AND ns.location_id = sm.location_id
                AND ns.status = :avail AND ns.deleted_at IS NULL
            WHERE COALESCE(ns.quantity, 0) < sm.alert_threshold
            ORDER BY (COALESCE(ns.quantity, 0) - sm.alert_threshold) ASC
            LIMIT 20
        ';
        return $this->connection->fetchAllAssociative($sql, ['avail' => 'available']);
    }

    /** @return array<array{type: string, total: int}> */
    private function movementsByTypeToday(): array
    {
        $sql = '
            SELECT type, COUNT(*) AS total
            FROM stock_movements
            WHERE DATE(created_at) = CURDATE()
            GROUP BY type
            ORDER BY total DESC
        ';
        return $this->connection->fetchAllAssociative($sql);
    }

    /** @return array<array{product: string, code: string, total_quantity: int}> */
    private function topUsedParts(): array
    {
        $sql = '
            SELECT p.name AS product, p.code, SUM(m.quantity) AS total_quantity
            FROM stock_movements m
            INNER JOIN products p ON p.id = m.product_id
            WHERE m.type = :exitType AND m.status = :completed
              AND m.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY p.id, p.name, p.code
            ORDER BY total_quantity DESC
            LIMIT 10
        ';
        return $this->connection->fetchAllAssociative($sql, [
            'exitType' => 'exit',
            'completed' => 'completed',
        ]);
    }
}
