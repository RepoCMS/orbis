<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CancelEmployeeSpecialPeriod;

final readonly class CancelEmployeeSpecialPeriodCommand
{
    public function __construct(
        public string $periodId,
        public string $employeeId,
    ) {
    }
}
