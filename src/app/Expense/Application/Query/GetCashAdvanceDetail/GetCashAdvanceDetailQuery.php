<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\GetCashAdvanceDetail;

final readonly class GetCashAdvanceDetailQuery
{
    public function __construct(
        public string $cashAdvanceId,
    ) {
    }
}
