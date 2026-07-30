<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\ApproveExpenseItem;

final readonly class ApproveExpenseItemCommand
{
    public function __construct(
        public string $expenseId,
        public string $itemId,
        public string $reviewedBy,
    ) {}
}
