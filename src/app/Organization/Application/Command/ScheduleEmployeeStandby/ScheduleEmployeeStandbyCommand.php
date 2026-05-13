<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ScheduleEmployeeStandby;

final readonly class ScheduleEmployeeStandbyCommand
{
    public function __construct(
        public string  $employeeId,
        public string  $startedAt,  // Y-m-d
        public string  $endedAt,    // Y-m-d
        public ?string $notes = null,
    ) {
    }
}
