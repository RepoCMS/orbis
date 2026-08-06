<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\PendingExpenseItems;

final readonly class PendingExpenseItemsQuery
{
    public function __construct(
        public ?string $employeeId = null,
    ) {
    }
}
