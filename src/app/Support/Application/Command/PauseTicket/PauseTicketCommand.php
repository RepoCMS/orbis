<?php

declare(strict_types=1);

namespace App\Support\Application\Command\PauseTicket;

final readonly class PauseTicketCommand
{
    public function __construct(
        public string $ticketId,
        public string $reason,
        public string $pausedBy,
    ) {
    }
}
