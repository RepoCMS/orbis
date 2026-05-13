<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CancelTicket;

final readonly class CancelTicketCommand
{
    public function __construct(
        public string $ticketId,
        public string $reason,
        public string $cancelledBy,
    ) {}
}
