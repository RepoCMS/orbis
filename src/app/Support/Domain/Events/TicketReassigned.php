<?php

declare(strict_types=1);

namespace App\Support\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class TicketReassigned extends DomainEvent
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $newEmployeeId,
        public readonly string $previousEmployeeId,
    ) {
        parent::__construct();
    }
}
