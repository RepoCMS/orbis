<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RequestReopenTicket;

final readonly class RequestReopenTicketCommand
{
    public function __construct(
        public string $ticketId,
        public string $reason,
        public string $requestedBy,
    ) {}
}
