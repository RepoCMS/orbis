<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetTicketDetail;

use App\Support\Application\Query\Response\TicketResponse;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class GetTicketDetailHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {}

    public function __invoke(GetTicketDetailQuery $query): TicketResponse
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($query->ticketId)
        );

        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$query->ticketId} no encontrado.");
        }

        return TicketResponse::fromEntity($ticket);
    }
}
