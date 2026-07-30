<?php

declare(strict_types=1);

namespace App\Expense\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class ExpenseSubmitted extends DomainEvent
{
    public function __construct(
        public readonly string $expenseId,
        public readonly string $employeeId,
        public readonly int $totalItems,
        public readonly float $totalAmount,
    ) {
        parent::__construct();
    }
}
