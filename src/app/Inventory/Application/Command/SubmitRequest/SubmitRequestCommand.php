<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\SubmitRequest;

final readonly class SubmitRequestCommand
{
    public function __construct(
        public string $productId,
        public ?string $serializedStockId,
        public int $quantity,
        public string $sourceLocationId,
        public string $destinationLocationId,
        public string $requestedBy,
        public ?string $notes,
    ) {
    }
}
