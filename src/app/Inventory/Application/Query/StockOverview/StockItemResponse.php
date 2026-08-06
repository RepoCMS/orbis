<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\StockOverview;

final class StockItemResponse
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $type,           // 'serialized' | 'nonserialized'
        public readonly string  $productId,
        public readonly string  $productCode,
        public readonly string  $productName,
        public readonly string  $locationId,
        public readonly string  $locationName,
        public readonly string  $status,
        public readonly string  $statusLabel,
        public readonly int     $quantity,
        public readonly ?string $serialNumber,
    ) {
    }
}
