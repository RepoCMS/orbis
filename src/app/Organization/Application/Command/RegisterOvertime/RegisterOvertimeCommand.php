<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RegisterOvertime;

final readonly class RegisterOvertimeCommand
{
    public function __construct(
        public string $employeeId,
        public string $workedAt,
        public string $startTime,
        public string $endTime,
        public string $reason,
        public ?string $evidenceUrl = null,
    ) {
    }
}
