<?php

declare(strict_types=1);

namespace App\Expense\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class CashAdvanceUpdated extends DomainEvent
{
    public function __construct(
        public readonly string $cashAdvanceId,
        public readonly float $previousAmount,
        public readonly float $newAmount,
    ) {
        parent::__construct();
    }
}
