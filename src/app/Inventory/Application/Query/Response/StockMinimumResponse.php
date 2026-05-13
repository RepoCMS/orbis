<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\StockMinimum;

final readonly class StockMinimumResponse
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $locationId,
        public int    $idealQuantity,
        public int    $alertThreshold,
    ) {
    }

    public static function fromEntity(StockMinimum $minimum): self
    {
        return new self(
            id:             (string) $minimum->id(),
            productId:      (string) $minimum->productId(),
            locationId:     (string) $minimum->locationId(),
            idealQuantity:  $minimum->idealQuantity(),
            alertThreshold: $minimum->alertThreshold(),
        );
    }
}
