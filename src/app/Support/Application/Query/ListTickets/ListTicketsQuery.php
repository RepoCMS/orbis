<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTickets;

use App\Support\Domain\Enums\TicketStatus;

final readonly class ListTicketsQuery
{
    /**
     * @param TicketStatus[]|null $statuses
     */
    public function __construct(
        public ?string $clientId      = null,
        public ?string $employeeId    = null,
        public ?array  $statuses      = null,
        public ?string $dateFrom      = null,
        public ?string $dateTo        = null,
        public int     $page          = 1,
        public int     $perPage       = 25,
    ) {
    }
}
