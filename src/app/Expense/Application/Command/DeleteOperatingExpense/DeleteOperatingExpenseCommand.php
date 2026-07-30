<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\DeleteOperatingExpense;

final readonly class DeleteOperatingExpenseCommand
{
    public function __construct(
        public string $expenseId,
    ) {}
}
