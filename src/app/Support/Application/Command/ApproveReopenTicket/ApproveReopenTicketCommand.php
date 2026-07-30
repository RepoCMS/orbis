<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ApproveReopenTicket;

final readonly class ApproveReopenTicketCommand
{
    public function __construct(
        public string $ticketId,
        public string $approvedBy,
    ) {}
}
