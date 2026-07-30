<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\InventoryDashboardMetrics\InventoryDashboardMetrics;
use App\Inventory\Application\Query\InventoryDashboardMetrics\InventoryDashboardMetricsHandler;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class InventoryDashboardMetricsHandlerTest extends TestCase
{
    private function mockConnection(
        int|float $fetchOneValue = 42,
        array $fetchAllValue = [],
        array $fillRateRow = ['total_items' => 100, 'fulfilled' => 80],
    ): Connection {
        $connection = $this->createMock(Connection::class);

        $connection->method('fetchOne')->willReturn($fetchOneValue);

        $connection->method('fetchAllAssociative')->willReturn($fetchAllValue);

        $connection->method('fetchAssociative')->willReturn($fillRateRow);

        return $connection;
    }

    public function testReturnsMetricsWithExpectedShape(): void
    {
        $connection = $this->mockConnection();
        $handler    = new InventoryDashboardMetricsHandler($connection);
        $result     = ($handler)();

        self::assertInstanceOf(InventoryDashboardMetrics::class, $result);
        self::assertSame(42.0, $result->totalInventoryValue);
        self::assertSame(42, $result->totalProducts);
        self::assertSame(42, $result->totalSerializedStock);
        self::assertSame(42, $result->totalNonSerializedStock);
        self::assertSame(42, $result->deadStockCount);
        self::assertSame(42, $result->itemsWithoutMovement90d);
        self::assertSame(100, $result->fillRateItems);
        self::assertSame(80.0, $result->fillRatePct);
        self::assertSame(42, $result->pendingRequests);
        self::assertSame(42, $result->openRepairs);
        self::assertSame(42, $result->openPurchaseOrders);
        self::assertSame(42, $result->physicalCountsPending);
        self::assertSame(42, $result->pendingReviewCount);
        self::assertSame(42, $result->stockAlertsCount);
        self::assertSame(42.0, $result->avgRepairTimeHours);
        self::assertSame(42, $result->movementsToday);
        self::assertSame([], $result->abcAnalysis);
        self::assertSame([], $result->deadStock90d);
        self::assertSame([], $result->valueByCategory);
        self::assertSame([], $result->movementsByType);
        self::assertSame([], $result->stockByLocation);
        self::assertSame([], $result->stockAlerts);
        self::assertSame([], $result->movementsByTypeToday);
        self::assertSame([], $result->topUsedParts);
    }

    public function testReturnsZeroWhenNoData(): void
    {
        $connection = $this->mockConnection(fetchOneValue: 0, fillRateRow: ['total_items' => 0, 'fulfilled' => 0]);
        $handler    = new InventoryDashboardMetricsHandler($connection);
        $result     = ($handler)();

        self::assertSame(0.0, $result->totalInventoryValue);
        self::assertSame(0, $result->totalProducts);
        self::assertSame(0, $result->fillRateItems);
        self::assertSame(0.0, $result->fillRatePct);
        self::assertSame(0.0, $result->avgRepairTimeHours);
    }

    public function testUsesFillRateCalculation(): void
    {
        $fillRateRow = ['total_items' => 10, 'fulfilled' => 7];
        $connection  = $this->mockConnection(fetchOneValue: 5, fillRateRow: $fillRateRow);
        $handler     = new InventoryDashboardMetricsHandler($connection);
        $result      = ($handler)();

        self::assertSame(10, $result->fillRateItems);
        self::assertSame(70.0, $result->fillRatePct);
    }

    public function testHandlesFillRateWithZeroTotal(): void
    {
        $fillRateRow = ['total_items' => 0, 'fulfilled' => 0];
        $connection  = $this->mockConnection(fetchOneValue: 0, fillRateRow: $fillRateRow);
        $handler     = new InventoryDashboardMetricsHandler($connection);
        $result      = ($handler)();

        self::assertSame(0, $result->fillRateItems);
        self::assertSame(0.0, $result->fillRatePct);
    }

    public function testReturnsComplexArraysFromFetchAllAssociative(): void
    {
        $movementsData = [
            ['type' => 'exit', 'total' => 15],
            ['type' => 'entry', 'total' => 10],
        ];

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(7);
        $connection->method('fetchAllAssociative')->willReturn($movementsData);
        $connection->method('fetchAssociative')->willReturn(['total_items' => 30, 'fulfilled' => 15]);

        $handler = new InventoryDashboardMetricsHandler($connection);
        $result  = ($handler)();

        self::assertNotEmpty($result->abcAnalysis);
        self::assertNotEmpty($result->deadStock90d);
        self::assertNotEmpty($result->valueByCategory);
        self::assertNotEmpty($result->movementsByType);
        self::assertNotEmpty($result->stockByLocation);
        self::assertNotEmpty($result->stockAlerts);
        self::assertSame(7, $result->totalProducts);
    }
}
