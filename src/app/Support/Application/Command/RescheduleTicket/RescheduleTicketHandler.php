<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RescheduleTicket;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\TicketReschedule;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\Repositories\TicketRescheduleRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketRescheduleIdValueObject;

final readonly class RescheduleTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface           $ticketRepo,
        private TicketRescheduleRepositoryInterface $rescheduleRepo,
        private IdGeneratorInterface                $idGenerator,
        private DomainEventPublisherInterface       $eventPublisher,
    ) {
    }

    public function __invoke(RescheduleTicketCommand $command): void
    {
        $ticketId = TicketIdValueObject::fromString($command->ticketId);

        $ticket = $this->ticketRepo->findById($ticketId);
        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$command->ticketId} no encontrado.");
        }

        $previousDate = $ticket->scheduledAt() ?? new \DateTimeImmutable();

        $reschedule = TicketReschedule::create(
            id:                  TicketRescheduleIdValueObject::fromString($this->idGenerator->generate()),
            ticketId:            $ticketId,
            previousScheduledAt: $previousDate,
            newScheduledAt:      $command->newScheduledAt,
            reason:              $command->reason,
            requestedBy:         $command->requestedBy,
            rescheduledBy:       $command->rescheduledBy,
        );

        $ticket->reschedule(
            newDate:             $command->newScheduledAt,
            reason:              $command->reason,
            requestedBy:         $command->requestedBy,
            rescheduledBy:       $command->rescheduledBy,
            rescheduleRecord:    $reschedule,
            slaMaxResponseHours: $command->slaMaxResponseHours,
        );

        $this->ticketRepo->save($ticket);
        $this->rescheduleRepo->save($reschedule);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
