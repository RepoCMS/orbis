<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\SerializedStock;

final readonly class CustodyAssignmentResponse
{
    public function __construct(
        public string $custodyLocationId,
        public string $custodyLocationName,
        public string $employeeId,
        public string $stockItemId,
        public string $serialNumber,
        public string $productId,
        public bool $isAvailable,
    ) {
    }

    public static function fromEntities(Location $location, SerializedStock $stock): self
    {
        return new self(
            custodyLocationId:   (string) $location->id(),
            custodyLocationName: (string) $location->name(),
            employeeId:          $location->employeeId() ?? '',
            stockItemId:         (string) $stock->id(),
            serialNumber:        (string) $stock->serialNumber(),
            productId:           (string) $stock->productId(),
            isAvailable:         $stock->isAvailable(),
        );
    }
}
