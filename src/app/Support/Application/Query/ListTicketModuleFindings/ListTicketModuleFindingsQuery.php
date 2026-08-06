<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTicketModuleFindings;

final readonly class ListTicketModuleFindingsQuery
{
    public function __construct(
        public string $ticketId,
    ) {
    }
}
