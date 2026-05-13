<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\SerializedStock;

final readonly class SerializedStockResponse
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $serialNumber,
        public string $locationId,
        public string $locationName,
        public string $status,
    ) {
    }

    public static function fromEntity(SerializedStock $stock, string $locationName = ''): self
    {
        return new self(
            id:           (string) $stock->id(),
            productId:    (string) $stock->productId(),
            serialNumber: (string) $stock->serialNumber(),
            locationId:   (string) $stock->locationId(),
            locationName: $locationName,
            status:       $stock->status()->value,
        );
    }
}
