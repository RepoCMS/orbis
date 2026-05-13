<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DefineStockMinimum;

final readonly class DefineStockMinimumCommand
{
    public function __construct(
        public string $productId,
        public string $locationId,
        public int    $idealQuantity,
        public int    $alertThreshold,
    ) {
    }
}
