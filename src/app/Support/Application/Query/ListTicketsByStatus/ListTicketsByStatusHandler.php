<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTicketsByStatus;

use App\Support\Application\Query\Response\TicketResponse;
use App\Support\Domain\Repositories\TicketRepositoryInterface;

final readonly class ListTicketsByStatusHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {
    }

    /** @return TicketResponse[] */
    public function __invoke(ListTicketsByStatusQuery $query): array
    {
        $tickets = $this->ticketRepo->findByStatuses([$query->status]);

        return array_map(
            fn ($ticket) => TicketResponse::fromEntity($ticket),
            $tickets
        );
    }
}
