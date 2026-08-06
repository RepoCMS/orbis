<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTickets;

use App\Support\Application\Query\Response\TicketResponse;
use App\Support\Domain\Repositories\TicketRepositoryInterface;

final readonly class ListTicketsHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {
    }

    /** @return TicketResponse[] */
    public function __invoke(ListTicketsQuery $query): array
    {
        if ($query->clientId !== null) {
            $tickets = $this->ticketRepo->findByClientId($query->clientId);
        } elseif ($query->employeeId !== null) {
            $tickets = $this->ticketRepo->findByEmployeeId($query->employeeId);
        } elseif ($query->statuses !== null && count($query->statuses) > 0) {
            $tickets = $this->ticketRepo->findByStatuses($query->statuses);
        } else {
            $tickets = $this->ticketRepo->findAll();
        }

        return array_map(
            fn ($ticket) => TicketResponse::fromEntity($ticket),
            $tickets
        );
    }
}
