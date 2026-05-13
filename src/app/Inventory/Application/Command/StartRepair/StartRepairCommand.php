<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\StartRepair;

final readonly class StartRepairCommand
{
    public function __construct(
        public string $id,
        public string $technicianId,
    ) {
    }
}
