<?php

declare(strict_types=1);

namespace App\Expense\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class CashAdvanceSettled extends DomainEvent
{
    public function __construct(
        public readonly string $cashAdvanceId,
        public readonly string $employeeId,
        public readonly float $amount,
        public readonly string $direction,
        public readonly string $confirmedBy,
    ) {
        parent::__construct();
    }
}
