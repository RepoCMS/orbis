<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\StockOverview;

final class StockOverviewResult
{
    /** @param StockItemResponse[] $items */
    public function __construct(
        public readonly array $items,
        public readonly int   $total,
        public readonly int   $page,
        public readonly int   $perPage,
        public readonly int   $totalAvailable,
        public readonly int   $totalDamaged,
        public readonly int   $totalReserved,
        public readonly int   $totalDecommissioned,
    ) {}

    public function totalPages(): int
    {
        return (int) ceil($this->total / max(1, $this->perPage));
    }
}
