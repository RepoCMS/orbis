<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CompleteEmployeeSpecialPeriod;

final readonly class CompleteEmployeeSpecialPeriodCommand
{
    public function __construct(
        public string  $periodId,
        public string  $employeeId,
        public ?string $realEndDate = null,  // Y-m-d (opcional)
    ) {
    }
}
