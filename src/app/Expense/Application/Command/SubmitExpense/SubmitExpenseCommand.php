<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\SubmitExpense;

final readonly class SubmitExpenseCommand
{
    public function __construct(
        public string $expenseId,
    ) {}
}
