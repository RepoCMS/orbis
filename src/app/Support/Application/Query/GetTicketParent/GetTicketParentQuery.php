<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetTicketParent;

final readonly class GetTicketParentQuery
{
    public function __construct(
        public string $ticketId,
    ) {
    }
}
