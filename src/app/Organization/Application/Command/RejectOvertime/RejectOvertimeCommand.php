<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RejectOvertime;

final readonly class RejectOvertimeCommand
{
    public function __construct(
        public string $overtimeRecordId,
        public string $reviewedBy,
        public string $reason,
    ) {}
}
