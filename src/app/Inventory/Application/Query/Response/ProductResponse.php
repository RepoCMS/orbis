<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\Product;

final readonly class ProductResponse
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $description,
        public string $trackingType,
    ) {
    }

    public static function fromEntity(Product $product): self
    {
        return new self(
            id:           (string) $product->id(),
            code:         (string) $product->productCode(),
            name:         (string) $product->name(),
            description:  $product->description(),
            trackingType: $product->trackingType()->value,
        );
    }
}
