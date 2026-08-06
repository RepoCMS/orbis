<?php

declare(strict_types=1);

namespace App\Support\Application\Query\TicketsByRoute;

final readonly class TicketsByRouteQuery
{
    public function __construct(
        public string $routeGroupId,
    ) {
    }
}
