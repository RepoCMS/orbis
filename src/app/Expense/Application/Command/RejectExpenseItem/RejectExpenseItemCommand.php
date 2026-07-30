<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\RejectExpenseItem;

final readonly class RejectExpenseItemCommand
{
    public function __construct(
        public string $expenseId,
        public string $itemId,
        public string $rejectionReason,
        public string $reviewedBy,
    ) {}
}
