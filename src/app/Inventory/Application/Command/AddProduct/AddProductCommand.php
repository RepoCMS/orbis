<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AddProduct;

final readonly class AddProductCommand
{
    public function __construct(
        public string $code,
        public string $name,
        public string $description,
        public string $trackingType,
    ) {
    }
}
