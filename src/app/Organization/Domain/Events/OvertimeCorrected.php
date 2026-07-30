<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class OvertimeCorrected extends DomainEvent
{
    public function __construct(
        public readonly string $overtimeRecordId,
        public readonly string $employeeId,
        public readonly string $workedAt,
        public readonly string $startTime,
        public readonly string $endTime,
    ) {
        parent::__construct();
    }
}
