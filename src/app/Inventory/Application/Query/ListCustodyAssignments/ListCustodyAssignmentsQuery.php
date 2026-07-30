<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListCustodyAssignments;

final readonly class ListCustodyAssignmentsQuery
{
    public function __construct(
        public ?string $employeeId = null,
        public ?string $locationId = null,
    ) {
    }
}
