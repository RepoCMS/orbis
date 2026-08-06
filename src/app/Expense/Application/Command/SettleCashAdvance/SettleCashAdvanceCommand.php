<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\SettleCashAdvance;

final readonly class SettleCashAdvanceCommand
{
    public function __construct(
        public string $cashAdvanceId,
        public string $settlementConfirmedBy,
        public string $settlementDirection,
    ) {
    }
}
