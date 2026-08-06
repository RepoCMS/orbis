<?php

declare(strict_types=1);

namespace App\Inventory\Application\ValueObjects;

final readonly class RequestItemCommandInput
{
    public function __construct(
        public string $productId,
        public ?string $serializedStockId,
        public int $quantity,
    ) {
    }
}
