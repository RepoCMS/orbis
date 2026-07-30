<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\UpdateOperatingExpense;

final readonly class UpdateOperatingExpenseCommand
{
    public function __construct(
        public string $expenseId,
        public string $reason,
        public string $startDate,
        public string $endDate,
    ) {}
}
