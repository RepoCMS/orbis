<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\EditEmployeeSpecialPeriod;

final readonly class EditEmployeeSpecialPeriodCommand
{
    public function __construct(
        public string  $periodId,
        public string  $employeeId,
        public string  $startedAt,  // Y-m-d
        public string  $endedAt,    // Y-m-d
        public string  $type,       // VACATION | STANDBY
        public ?string $notes = null,
    ) {
    }
}
