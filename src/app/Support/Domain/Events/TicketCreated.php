<?php

declare(strict_types=1);

namespace App\Support\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class TicketCreated extends DomainEvent
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $ticketNumber,
        public readonly string $clientId,
        public readonly string $terminalAssignmentId,
        public readonly string $serviceType,
        public readonly string $createdBy,
    ) {
        parent::__construct();
    }
}
