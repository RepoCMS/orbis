<?php

declare(strict_types=1);

namespace App\Support\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class TicketAssigned extends DomainEvent
{
    /** @param string[] $employeeIds */
    public function __construct(
        public readonly string $ticketId,
        public readonly array  $employeeIds,
        public readonly string $inChargeEmployeeId,
        public readonly ?\DateTimeImmutable $scheduledAt,
    ) {
        parent::__construct();
    }
}
