<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AssignRepair;

final readonly class AssignRepairCommand
{
    public function __construct(
        public string $serializedStockId,
        public string $laboratoryId,
        public string $assignedById,
        public ?string $notes,
    ) {
    }
}
