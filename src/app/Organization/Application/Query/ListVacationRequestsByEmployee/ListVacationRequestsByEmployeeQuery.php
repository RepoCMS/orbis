<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListVacationRequestsByEmployee;

final readonly class ListVacationRequestsByEmployeeQuery
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}
