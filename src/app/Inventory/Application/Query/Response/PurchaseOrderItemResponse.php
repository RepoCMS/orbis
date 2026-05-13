<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\PurchaseOrderItem;

final readonly class PurchaseOrderItemResponse
{
    public function __construct(
        public string $id,
        public string $purchaseOrderId,
        public string $productId,
        public int $quantityOrdered,
        public int $quantityReceived,
        public int $quantityPending,
        public bool $fullyReceived,
    ) {
    }

    public static function fromEntity(PurchaseOrderItem $item): self
    {
        return new self(
            id:               (string) $item->id(),
            purchaseOrderId:  (string) $item->purchaseOrderId(),
            productId:        (string) $item->productId(),
            quantityOrdered:  $item->quantityOrdered(),
            quantityReceived: $item->quantityReceived(),
            quantityPending:  $item->quantityPending(),
            fullyReceived:    $item->isFullyReceived(),
        );
    }
}
