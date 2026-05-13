<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetTicketDetail;

final readonly class GetTicketDetailQuery
{
    public function __construct(
        public string $ticketId,
    ) {}
}
