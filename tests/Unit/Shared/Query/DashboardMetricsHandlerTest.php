<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Query;

use App\Shared\Application\Query\Dashboard\DashboardMetricsHandler;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class DashboardMetricsHandlerTest extends TestCase
{
    private function mockConnection(array $returnMap): Connection
    {
        $conn = $this->createMock(Connection::class);

        $conn->method('fetchOne')->willReturnCallback(
            function (string $sql, array $params = []) use ($returnMap) {
                $key = $this->normalizeSql($sql) . ($params !== [] ? '|' . json_encode($params) : '');
                if (isset($returnMap['fetchOne'][$key])) {
                    return $returnMap['fetchOne'][$key];
                }
                return 0;
            }
        );

        $conn->method('fetchAllAssociative')->willReturnCallback(
            function (string $sql) use ($returnMap) {
                $key = $this->normalizeSql($sql);
                if (isset($returnMap['fetchAllAssociative'][$key])) {
                    return $returnMap['fetchAllAssociative'][$key];
                }
                return [];
            }
        );

        return $conn;
    }

    private function normalizeSql(string $sql): string
    {
        return preg_replace('/\s+/', ' ', trim($sql));
    }

    public function testReturnsCompleteMetricsStructure(): void
    {
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $yesterday = (new \DateTimeImmutable('-1 day'))->format('Y-m-d');

        $returnMap = [
            'fetchOne' => [
                "SELECT COUNT(*) FROM tickets WHERE status NOT IN ('closed','cancelled') AND deleted_at IS NULL" => '15',
                "SELECT COUNT(*) FROM tickets WHERE DATE(scheduled_at) = :d AND deleted_at IS NULL|{\"d\":\"{$today}\"}" => '5',
                "SELECT COUNT(*) FROM tickets WHERE DATE(scheduled_at) = :d AND deleted_at IS NULL|{\"d\":\"{$yesterday}\"}" => '3',
                "SELECT COUNT(*) FROM tickets WHERE status = 'closed' AND DATE(closed_at) >= :d AND deleted_at IS NULL|" .
                '{"d":"' . (new \DateTimeImmutable('first day of this month'))->format('Y-m-d') . '"}' => '12',
                "SELECT COUNT(*) FROM repairs WHERE status IN ('pending', 'in_progress')" => '7',
                "SELECT COUNT(*) FROM repairs WHERE status = 'completed' AND DATE(completed_at) = :d|{\"d\":\"{$today}\"}" => '4',
                "SELECT COUNT(*) FROM repairs WHERE status = 'completed' AND DATE(completed_at) = :d|{\"d\":\"{$yesterday}\"}" => '2',
                "SELECT COUNT(*) FROM stock_movements WHERE DATE(created_at) = :d|{\"d\":\"{$today}\"}" => '20',
                "SELECT COUNT(*) FROM stock_movements WHERE DATE(created_at) = :d|{\"d\":\"{$yesterday}\"}" => '15',
            ],
            'fetchAllAssociative' => [
                "SELECT service_type, COUNT(*) as total FROM tickets WHERE status NOT IN ('closed','cancelled') " .
                'AND deleted_at IS NULL GROUP BY service_type ORDER BY total DESC' => [
                    ['service_type' => 'correctivo', 'total' => 8],
                    ['service_type' => 'preventivo', 'total' => 7],
                ],
                "SELECT p.name AS productName, p.code, l.name AS locationName, sm.alert_threshold, " .
                "COALESCE(ns.quantity, 0) AS current FROM stock_minimums sm INNER JOIN products p " .
                "ON p.id = sm.product_id AND p.deleted_at IS NULL INNER JOIN stock_locations l " .
                "ON l.id = sm.location_id AND l.deleted_at IS NULL LEFT JOIN nonserialized_stock ns " .
                "ON ns.product_id = sm.product_id AND ns.location_id = sm.location_id " .
                "AND ns.status = 'disponible' AND ns.deleted_at IS NULL " .
                "WHERE COALESCE(ns.quantity, 0) < sm.alert_threshold " .
                "ORDER BY (sm.alert_threshold - COALESCE(ns.quantity, 0)) DESC LIMIT 5" => [
                    ['productName' => 'Papel A4', 'code' => 'PAPEL', 'locationName' => 'Bodega Central',
                     'alert_threshold' => 50, 'current' => 10],
                ],
                "SELECT t.id, t.ticket_number AS ticketNumber, t.status, t.service_type AS serviceType, " .
                "t.scheduled_at AS scheduledAt FROM tickets t WHERE t.deleted_at IS NULL ORDER BY t.id DESC LIMIT 10" => [
                    ['id' => '01HZ001', 'ticketNumber' => 'TK-2026-001', 'status' => 'in_progress',
                     'serviceType' => 'correctivo', 'scheduledAt' => '2026-07-30 10:00:00'],
                ],
            ],
        ];

        $conn = $this->mockConnection($returnMap);
        $handler = new DashboardMetricsHandler($conn);
        $metrics = ($handler)();

        self::assertSame(15, $metrics->ticketsOpenTotal);
        self::assertSame(5, $metrics->ticketsCreatedToday);
        self::assertSame(3, $metrics->ticketsCreatedYesterday);
        self::assertSame(12, $metrics->ticketsClosedThisMonth);
        self::assertSame(7, $metrics->repairsInProgress);
        self::assertSame(4, $metrics->repairsCompletedToday);
        self::assertSame(2, $metrics->repairsCompletedYesterday);
        self::assertSame(20, $metrics->stockMovementsToday);
        self::assertSame(15, $metrics->stockMovementsYesterday);

        self::assertCount(2, $metrics->ticketsByServiceType);
        self::assertSame('correctivo', $metrics->ticketsByServiceType[0]['service_type']);
        self::assertSame(8, $metrics->ticketsByServiceType[0]['total']);

        self::assertCount(1, $metrics->stockAlerts);
        self::assertSame('Papel A4', $metrics->stockAlerts[0]['productName']);
        self::assertSame(10, $metrics->stockAlerts[0]['current']);

        self::assertCount(1, $metrics->recentTickets);
        self::assertSame('TK-2026-001', $metrics->recentTickets[0]['ticketNumber']);

        self::assertSame(2, $metrics->ticketsTodayVsYesterdayDiff());
        self::assertSame(2, $metrics->repairsVsYesterdayDiff());
        self::assertSame(5, $metrics->movementsVsYesterdayDiff());
    }

    public function testReturnsZeroValuesWhenNoData(): void
    {
        $conn = $this->createMock(Connection::class);
        $conn->method('fetchOne')->willReturn(0);
        $conn->method('fetchAllAssociative')->willReturn([]);

        $handler = new DashboardMetricsHandler($conn);
        $metrics = ($handler)();

        self::assertSame(0, $metrics->ticketsOpenTotal);
        self::assertSame(0, $metrics->ticketsCreatedToday);
        self::assertSame(0, $metrics->ticketsCreatedYesterday);
        self::assertSame(0, $metrics->repairsInProgress);
        self::assertCount(0, $metrics->ticketsByServiceType);
        self::assertCount(0, $metrics->stockAlerts);
        self::assertCount(0, $metrics->recentTickets);
    }
}
