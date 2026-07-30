<?php

declare(strict_types=1);

namespace App\Expense\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class ExpenseItemRejected extends DomainEvent
{
    public function __construct(
        public readonly string $expenseId,
        public readonly string $itemId,
        public readonly string $rejectionReason,
        public readonly string $reviewedBy,
    ) {
        parent::__construct();
    }
}
