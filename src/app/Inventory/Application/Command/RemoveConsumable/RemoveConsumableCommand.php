<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RemoveConsumable;

final readonly class RemoveConsumableCommand
{
    public function __construct(
        public string $repairId,
        public string $consumableId,
    ) {
    }
}
