<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\GetOperatingExpenseDetail;

final readonly class GetOperatingExpenseDetailQuery
{
    public function __construct(
        public string $expenseId,
    ) {}
}
