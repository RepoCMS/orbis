<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListEmployeeSpecialPeriods;

final readonly class ListEmployeeSpecialPeriodsQuery
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}
