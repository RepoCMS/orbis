<?php

declare(strict_types=1);

namespace App\Support\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class TicketPaused extends DomainEvent
{
    public function __construct(
        public readonly string             $ticketId,
        public readonly string             $reason,
        public readonly string             $pausedBy,
        public readonly \DateTimeImmutable $pausedAt,
    ) {
        parent::__construct();
    }
}
