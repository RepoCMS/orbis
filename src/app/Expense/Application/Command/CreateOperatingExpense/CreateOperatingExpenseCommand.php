<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\CreateOperatingExpense;

final readonly class CreateOperatingExpenseCommand
{
    public function __construct(
        public string $employeeId,
        public string $reason,
        public string $startDate,
        public string $endDate,
        public ?string $cashAdvanceId = null,
        public array $ticketIds = [],
    ) {}
}
