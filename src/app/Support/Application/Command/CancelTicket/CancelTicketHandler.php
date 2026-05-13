<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CancelTicket;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class CancelTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface     $ticketRepo,
        private DomainEventPublisherInterface $eventPublisher,
    ) {}

    public function __invoke(CancelTicketCommand $command): void
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($command->ticketId)
        );

        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$command->ticketId} no encontrado.");
        }

        $ticket->cancel($command->reason, $command->cancelledBy);

        $this->ticketRepo->save($ticket);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
