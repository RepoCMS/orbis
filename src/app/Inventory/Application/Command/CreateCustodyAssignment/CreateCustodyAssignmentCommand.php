<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateCustodyAssignment;

final readonly class CreateCustodyAssignmentCommand
{
    public function __construct(
        public string $employeeId,
        public string $stockItemId,
        public string $locationId,
        public ?string $notes = null,
    ) {
    }
}
