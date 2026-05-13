<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AddPurchaseOrderItem;

final readonly class AddPurchaseOrderItemCommand
{
    public function __construct(
        public string $purchaseOrderId,
        public string $productId,
        public int $quantityOrdered,
    ) {
    }
}
