<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CreateTicket;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class CreateTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface    $ticketRepo,
        private IdGeneratorInterface         $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(CreateTicketCommand $command): string
    {
        $id   = TicketIdValueObject::fromString($this->idGenerator->generate());
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $seq  = $this->ticketRepo->nextTicketSequence($year);

        $ticketNumber = sprintf('TK-%d-%06d', $year, $seq);

        $ticket = Ticket::create(
            id: $id,
            ticketNumber: $ticketNumber,
            clientId: $command->clientId,
            terminalAssignmentId: $command->terminalAssignmentId,
            serviceType: $command->serviceType,
            description: $command->description,
            createdBy: $command->createdBy,
        );

        $this->ticketRepo->save($ticket);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }

        return (string) $id;
    }
}
