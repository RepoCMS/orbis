<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTicketsByStatus;

use App\Support\Domain\Enums\TicketStatus;

final readonly class ListTicketsByStatusQuery
{
    public function __construct(
        public TicketStatus $status,
        public int $page = 1,
        public int $perPage = 25,
    ) {
    }
}
