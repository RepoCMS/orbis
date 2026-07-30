<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\FindProductsByPurchaseOrder;

final readonly class FindProductsByPurchaseOrderQuery
{
    public function __construct(
        public string $purchaseOrderId,
    ) {
    }
}
