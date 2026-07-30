<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class OvertimeReviewRequested extends DomainEvent
{
    public function __construct(
        public readonly string $overtimeRecordId,
        public readonly string $employeeId,
        public readonly string $reviewRequestedBy,
        public readonly ?string $correctionHint = null,
    ) {
        parent::__construct();
    }
}
