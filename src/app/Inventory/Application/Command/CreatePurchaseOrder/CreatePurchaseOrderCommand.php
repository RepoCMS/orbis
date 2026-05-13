<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreatePurchaseOrder;

final readonly class CreatePurchaseOrderCommand
{
    public function __construct(
        public string $reference,
        public ?string $notes,
        public string $createdBy,
    ) {
    }
}
