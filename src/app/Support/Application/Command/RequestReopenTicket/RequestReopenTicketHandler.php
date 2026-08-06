<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RequestReopenTicket;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class RequestReopenTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface     $ticketRepo,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(RequestReopenTicketCommand $command): void
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($command->ticketId)
        );

        if ($ticket === null) {
            throw TicketNotFoundException::withId($command->ticketId);
        }

        $ticket->requestReopen($command->reason, $command->requestedBy);

        $this->ticketRepo->save($ticket);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
