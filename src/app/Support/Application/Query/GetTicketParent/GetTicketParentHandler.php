<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetTicketParent;

use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Application\Query\Response\TicketResponse;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class GetTicketParentHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {
    }

    public function __invoke(GetTicketParentQuery $query): ?TicketResponse
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($query->ticketId)
        );

        if ($ticket === null) {
            throw TicketNotFoundException::withId($query->ticketId);
        }

        $parentId = $ticket->parentTicketId();
        if ($parentId === null) {
            return null;
        }

        $parent = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($parentId)
        );

        if ($parent === null) {
            return null;
        }

        return TicketResponse::fromEntity($parent);
    }
}
