<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\FindEmployeeById;

final readonly class FindEmployeeByIdQuery
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}
