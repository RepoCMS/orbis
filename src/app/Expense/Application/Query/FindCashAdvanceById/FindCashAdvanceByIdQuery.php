<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\FindCashAdvanceById;

final readonly class FindCashAdvanceByIdQuery
{
    public function __construct(
        public string $cashAdvanceId,
    ) {
    }
}
