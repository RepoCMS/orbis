<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CorrectOvertime;

final readonly class CorrectOvertimeCommand
{
    public function __construct(
        public string $overtimeRecordId,
        public string $workedAt,
        public string $startTime,
        public string $endTime,
        public string $reason,
        public ?string $evidenceUrl = null,
    ) {
    }
}
