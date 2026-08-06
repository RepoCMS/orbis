<?php

declare(strict_types=1);

namespace App\Support\Application\Command\UpdateTicket;

use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class UpdateTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {
    }

    public function __invoke(UpdateTicketCommand $command): void
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($command->ticketId)
        );

        if ($ticket === null) {
            throw TicketNotFoundException::withId($command->ticketId);
        }

        if ($command->description !== null) {
            $ticket->updateDescription($command->description);
        }

        if ($command->serviceType !== null) {
            $ticket->updateServiceType($command->serviceType);
        }

        $this->ticketRepo->save($ticket);
    }
}
