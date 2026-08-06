<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ReassignTicket;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Repositories\TicketAssignmentRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class ReassignTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface            $ticketRepo,
        private TicketAssignmentRepositoryInterface  $assignmentRepo,
        private IdGeneratorInterface                 $idGenerator,
        private DomainEventPublisherInterface        $eventPublisher,
    ) {
    }

    public function __invoke(ReassignTicketCommand $command): void
    {
        $ticketId = TicketIdValueObject::fromString($command->ticketId);

        $ticket = $this->ticketRepo->findById($ticketId);
        if ($ticket === null) {
            throw TicketNotFoundException::withId($command->ticketId);
        }

        $newAssignment = TicketAssignment::create(
            id:              TicketAssignmentIdValueObject::fromString($this->idGenerator->generate()),
            ticketId:        $ticketId,
            employeeId:      $command->newEmployeeId,
            isInCharge:      true,
            reassignedFrom:  $command->previousEmployeeId,
        );

        $ticket->reassign($newAssignment, $command->previousEmployeeId);

        $this->ticketRepo->save($ticket);
        $this->assignmentRepo->save($newAssignment);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
