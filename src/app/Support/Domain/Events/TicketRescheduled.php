<?php

declare(strict_types=1);

namespace App\Support\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class TicketRescheduled extends DomainEvent
{
    public function __construct(
        public readonly string             $ticketId,
        public readonly \DateTimeImmutable $previousScheduledAt,
        public readonly \DateTimeImmutable $newScheduledAt,
        public readonly string             $reason,
        public readonly string             $requestedBy,
        public readonly string             $rescheduledBy,
    ) {
        parent::__construct();
    }
}
