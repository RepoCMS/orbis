<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\EntryItem;

final readonly class EntryItemResponse
{
    public function __construct(
        public string $id,
        public string $entryId,
        public string $productId,
        public string $locationId,
        public ?string $serialNumber,
        public int $quantity,
        public bool $serialized,
        public ?string $purchaseOrderItemId,
    ) {
    }

    public static function fromEntity(EntryItem $item): self
    {
        return new self(
            id:                  (string) $item->id(),
            entryId:             (string) $item->entryId(),
            productId:           (string) $item->productId(),
            locationId:          (string) $item->locationId(),
            serialNumber:        $item->serialNumber() !== null ? (string) $item->serialNumber() : null,
            quantity:            $item->quantity(),
            serialized:          $item->isSerialized(),
            purchaseOrderItemId: $item->purchaseOrderItemId() !== null ? (string) $item->purchaseOrderItemId() : null,
        );
    }
}
