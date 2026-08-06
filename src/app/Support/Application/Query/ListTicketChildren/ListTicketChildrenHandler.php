<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTicketChildren;

use App\Support\Application\Query\Response\TicketResponse;
use App\Support\Domain\Repositories\TicketRepositoryInterface;

final readonly class ListTicketChildrenHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {
    }

    /** @return TicketResponse[] */
    public function __invoke(ListTicketChildrenQuery $query): array
    {
        $tickets = $this->ticketRepo->findByParentTicketId($query->parentTicketId);

        return array_map(
            fn ($ticket) => TicketResponse::fromEntity($ticket),
            $tickets
        );
    }
}
