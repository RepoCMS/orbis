<?php

declare(strict_types=1);

namespace App\Expense\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class ExpenseApproved extends DomainEvent
{
    public function __construct(
        public readonly string $expenseId,
        public readonly string $approvedBy,
        public readonly float $totalAmount,
    ) {
        parent::__construct();
    }
}
