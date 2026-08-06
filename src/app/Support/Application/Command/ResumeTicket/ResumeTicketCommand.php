<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ResumeTicket;

final readonly class ResumeTicketCommand
{
    public function __construct(
        public string $ticketId,
    ) {
    }
}
