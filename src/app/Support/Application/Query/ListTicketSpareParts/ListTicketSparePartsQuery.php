<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTicketSpareParts;

final readonly class ListTicketSparePartsQuery
{
    public function __construct(
        public ?string $ticketId = null,
        public ?string $employeeId = null,
        public ?string $userId = null,
    ) {}
}
