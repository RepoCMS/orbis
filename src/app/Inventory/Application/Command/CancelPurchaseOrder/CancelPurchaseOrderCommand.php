<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelPurchaseOrder;

final readonly class CancelPurchaseOrderCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
