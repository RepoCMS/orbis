<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\ListOperatingExpenses;

final readonly class ListOperatingExpensesQuery
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        public ?string $employeeId = null,
        public ?string $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $ticketId = null,
    ) {}
}
