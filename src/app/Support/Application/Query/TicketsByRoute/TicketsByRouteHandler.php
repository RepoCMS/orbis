<?php

declare(strict_types=1);

namespace App\Support\Application\Query\TicketsByRoute;

use App\Support\Application\Query\Response\TicketResponse;
use App\Support\Domain\Repositories\TicketRepositoryInterface;

final readonly class TicketsByRouteHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {
    }

    /** @return TicketResponse[] */
    public function __invoke(TicketsByRouteQuery $query): array
    {
        $tickets = $this->ticketRepo->findByRouteGroupId($query->routeGroupId);

        return array_map(
            fn ($ticket) => TicketResponse::fromEntity($ticket),
            $tickets
        );
    }
}
