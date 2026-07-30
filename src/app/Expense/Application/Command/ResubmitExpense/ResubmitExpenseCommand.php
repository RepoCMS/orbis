<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\ResubmitExpense;

final readonly class ResubmitExpenseCommand
{
    public function __construct(
        public string $expenseId,
    ) {}
}
