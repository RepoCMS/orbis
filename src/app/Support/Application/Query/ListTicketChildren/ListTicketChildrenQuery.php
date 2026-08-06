<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTicketChildren;

final readonly class ListTicketChildrenQuery
{
    public function __construct(
        public string $parentTicketId,
    ) {
    }
}
