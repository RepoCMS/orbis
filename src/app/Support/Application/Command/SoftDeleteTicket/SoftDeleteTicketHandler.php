<?php

declare(strict_types=1);

namespace App\Support\Application\Command\SoftDeleteTicket;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class SoftDeleteTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface     $ticketRepo,
        private DomainEventPublisherInterface $eventPublisher,
    ) {}

    public function __invoke(SoftDeleteTicketCommand $command): void
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($command->ticketId)
        );

        if ($ticket === null) {
            throw TicketNotFoundException::withId($command->ticketId);
        }

        $ticket->softDelete();

        $this->ticketRepo->save($ticket);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
