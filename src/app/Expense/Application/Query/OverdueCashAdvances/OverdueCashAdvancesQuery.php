<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\OverdueCashAdvances;

final readonly class OverdueCashAdvancesQuery
{
    public function __construct(
        public int $cutoffDays = 3,
    ) {
    }
}
