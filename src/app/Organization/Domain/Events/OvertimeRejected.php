<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class OvertimeRejected extends DomainEvent
{
    public function __construct(
        public readonly string $overtimeRecordId,
        public readonly string $employeeId,
        public readonly string $rejectedBy,
        public readonly string $reason,
    ) {
        parent::__construct();
    }
}
