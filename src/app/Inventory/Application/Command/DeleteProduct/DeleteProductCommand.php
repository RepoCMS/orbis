<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DeleteProduct;

final readonly class DeleteProductCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
