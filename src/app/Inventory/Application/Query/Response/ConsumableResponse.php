<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\Consumable;

final readonly class ConsumableResponse
{
    public function __construct(
        public string $id,
        public string $repairId,
        public string $productId,
        public string $productCode,
        public string $productName,
        public int $quantity,
        public ?float $unitCost,
        public ?string $notes,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Consumable $consumable): self
    {
        return new self(
            id:          (string) $consumable->id(),
            repairId:    (string) $consumable->repairId(),
            productId:   $consumable->productId(),
            productCode: $consumable->productCode(),
            productName: $consumable->productName(),
            quantity:    $consumable->quantity(),
            unitCost:    $consumable->unitCost(),
            notes:       $consumable->notes(),
            createdAt:   $consumable->createdAt()->format('Y-m-d H:i:s'),
        );
    }
}
