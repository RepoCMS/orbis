<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ReassignTicket;

final readonly class ReassignTicketCommand
{
    public function __construct(
        public string              $ticketId,
        public string              $newEmployeeId,
        public string              $previousEmployeeId,
        public string              $reassignedBy,
        public ?\DateTimeImmutable $scheduledAt = null,
    ) {}
}
