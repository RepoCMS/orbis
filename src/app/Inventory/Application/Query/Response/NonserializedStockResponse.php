<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\NonserializedStock;

final readonly class NonserializedStockResponse
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $locationId,
        public string $locationName,
        public int $quantity,
        public string $status,
    ) {
    }

    public static function fromEntity(NonserializedStock $stock, string $locationName = ''): self
    {
        return new self(
            id:           (string) $stock->id(),
            productId:    (string) $stock->productId(),
            locationId:   (string) $stock->locationId(),
            locationName: $locationName,
            quantity:     $stock->quantity(),
            status:       $stock->status()->value,
        );
    }
}
