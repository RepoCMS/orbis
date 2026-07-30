<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\ApproveOperatingExpense;

final readonly class ApproveOperatingExpenseCommand
{
    public function __construct(
        public string $expenseId,
        public string $approvedBy,
    ) {}
}
