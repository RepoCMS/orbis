<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\UpdatePurchaseOrder;

final readonly class UpdatePurchaseOrderCommand
{
    public function __construct(
        public string $purchaseOrderId,
        public string $reference,
        public ?string $notes,
    ) {
    }
}
