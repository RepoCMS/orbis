<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListOvertimeRecordsByEmployee;

final readonly class ListOvertimeRecordsByEmployeeQuery
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}
