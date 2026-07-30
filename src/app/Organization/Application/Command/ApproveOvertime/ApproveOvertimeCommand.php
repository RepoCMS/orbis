<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ApproveOvertime;

final readonly class ApproveOvertimeCommand
{
    public function __construct(
        public string $overtimeRecordId,
        public string $reviewedBy,
    ) {}
}
