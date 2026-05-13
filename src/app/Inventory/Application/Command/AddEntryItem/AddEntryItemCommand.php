<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AddEntryItem;

final readonly class AddEntryItemCommand
{
    public function __construct(
        public string $entryId,
        public string $productId,
        public string $locationId,
        public ?string $serialNumber,
        public int $quantity,
        public ?string $purchaseOrderItemId,
    ) {
    }
}
