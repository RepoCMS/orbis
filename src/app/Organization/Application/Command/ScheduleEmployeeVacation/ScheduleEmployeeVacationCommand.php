<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ScheduleEmployeeVacation;

final readonly class ScheduleEmployeeVacationCommand
{
    public function __construct(
        public string $employeeId,
        public string $startedAt,  // Y-m-d
        public string $endedAt,    // Y-m-d
    ) {
    }
}
