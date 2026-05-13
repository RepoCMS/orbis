<?php

declare(strict_types=1);

namespace App\Support\Application\Command\AssignTicket;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class AssignTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface     $ticketRepo,
        private IdGeneratorInterface          $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {}

    public function __invoke(AssignTicketCommand $command): void
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($command->ticketId)
        );

        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$command->ticketId} no encontrado.");
        }

        if (!in_array($command->inChargeId, $command->employeeIds, true)) {
            throw new \InvalidArgumentException('El técnico encargado debe estar en la lista de técnicos asignados.');
        }

        $ticketId     = TicketIdValueObject::fromString($command->ticketId);
        $assignments  = [];

        foreach ($command->employeeIds as $empId) {
            $assignments[] = TicketAssignment::create(
                id: TicketAssignmentIdValueObject::fromString($this->idGenerator->generate()),
                ticketId: $ticketId,
                employeeId: $empId,
                isInCharge: ($empId === $command->inChargeId),
            );
        }

        $ticket->assign($assignments, $command->scheduledAt, $command->slaMaxResponseHours);

        $this->ticketRepo->save($ticket);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
