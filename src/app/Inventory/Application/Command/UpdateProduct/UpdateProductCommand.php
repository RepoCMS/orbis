<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\UpdateProduct;

final readonly class UpdateProductCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public ?string $trackingType = null,
    ) {
    }
}
