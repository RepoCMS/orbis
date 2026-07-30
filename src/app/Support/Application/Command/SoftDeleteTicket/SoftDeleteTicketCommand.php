<?php

declare(strict_types=1);

namespace App\Support\Application\Command\SoftDeleteTicket;

final readonly class SoftDeleteTicketCommand
{
    public function __construct(
        public string $ticketId,
    ) {}
}
