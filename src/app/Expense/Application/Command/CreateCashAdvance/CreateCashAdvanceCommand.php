<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\CreateCashAdvance;

final readonly class CreateCashAdvanceCommand
{
    public function __construct(
        public string $employeeId,
        public string $givenBy,
        public float $amount,
        public string $givenAt,
    ) {
    }
}
