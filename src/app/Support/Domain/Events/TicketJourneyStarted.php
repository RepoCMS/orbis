<?php

declare(strict_types=1);

namespace App\Support\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class TicketJourneyStarted extends DomainEvent
{
    public function __construct(
        public readonly string             $ticketId,
        public readonly string             $journeyType,
        public readonly \DateTimeImmutable $startedAt,
    ) {
        parent::__construct();
    }
}
