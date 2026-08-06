<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\UpdateCashAdvance;

final readonly class UpdateCashAdvanceCommand
{
    public function __construct(
        public string $cashAdvanceId,
        public float $newAmount,
    ) {
    }
}
