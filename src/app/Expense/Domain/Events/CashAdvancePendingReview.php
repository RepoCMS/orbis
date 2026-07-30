<?php

declare(strict_types=1);

namespace App\Expense\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class CashAdvancePendingReview extends DomainEvent
{
    public function __construct(
        public readonly string $cashAdvanceId,
        public readonly string $employeeId,
        public readonly string $givenBy,
        public readonly float $amount,
    ) {
        parent::__construct();
    }
}
