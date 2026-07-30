<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RegisterConsumable;

final readonly class RegisterConsumableCommand
{
    public function __construct(
        public string $repairId,
        public string $productId,
        public string $productCode,
        public string $productName,
        public int $quantity,
        public ?float $unitCost = null,
        public ?string $notes = null,
    ) {
    }
}
