<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\InventoryDashboardMetrics;

final class InventoryDashboardMetrics
{
    /**
     * @param array<array{product: string, code: string, total_value: float, abc_class: string}> $abcAnalysis
     * @param array<array{product_name: string, code: string, serial: string, days_in_stock: int}> $deadStock90d
     * @param array<array{total: int, fulfilled: int, fill_rate: float}> $fillRate
     * @param array<array{category: string, total: float}> $valueByCategory
     * @param array<array{type: string, total: int}> $movementsByType
     * @param array<array{location: string, serialized: int, nonserialized: int}> $stockByLocation
     * @param array<array{product: string, location: string, current: int, threshold: int}> $stockAlerts
     * @param array<array{type: string, total: int}> $movementsByTypeToday
     * @param array<array{product: string, code: string, total_quantity: int}> $topUsedParts
     */
    public function __construct(
        public readonly float  $totalInventoryValue,
        public readonly int    $totalProducts,
        public readonly int    $totalSerializedStock,
        public readonly int    $totalNonSerializedStock,
        public readonly int    $deadStockCount,
        public readonly int    $itemsWithoutMovement90d,
        public readonly int    $fillRateItems,
        public readonly float  $fillRatePct,
        public readonly int    $pendingRequests,
        public readonly int    $openRepairs,
        public readonly int    $openPurchaseOrders,
        public readonly int    $physicalCountsPending,
        public readonly int    $pendingReviewCount,
        public readonly int    $stockAlertsCount,
        public readonly float  $avgRepairTimeHours,
        public readonly int    $movementsToday,
        public readonly array  $abcAnalysis,
        public readonly array  $deadStock90d,
        public readonly array  $fillRate,
        public readonly array  $valueByCategory,
        public readonly array  $movementsByType,
        public readonly array  $stockByLocation,
        public readonly array  $stockAlerts,
        public readonly array  $movementsByTypeToday = [],
        public readonly array  $topUsedParts = [],
    ) {}
}
