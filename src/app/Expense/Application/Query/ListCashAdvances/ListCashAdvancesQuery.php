<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\ListCashAdvances;

final readonly class ListCashAdvancesQuery
{
    public function __construct(
        public ?string $employeeId = null,
        public ?string $status = null,
        public ?int $daysOverdue = null,
    ) {
    }
}
