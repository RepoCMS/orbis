<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\RescheduleTicket\RescheduleTicketCommand;
use App\Support\Application\Command\RescheduleTicket\RescheduleTicketHandler;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Enums\RescheduleRequestedBy;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\Repositories\TicketRescheduleRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class RescheduleTicketHandlerTest extends TestCase
{
    private const TICKET_ID         = '01HZ0000000000000000000001';
    private const CLIENT_ID         = '01HZ0000000000000000000002';
    private const TERMINAL_ID       = '01HZ0000000000000000000003';
    private const CREATED_BY        = '01HZ0000000000000000000004';
    private const EMPLOYEE_ID_1     = '01HZ0000000000000000000005';
    private const ASSIGNMENT_ID_1   = '01HZ0000000000000000000006';
    private const RESCHEDULE_ID     = '01HZ0000000000000000000007';
    private const RESCHEDULED_BY    = '01HZ0000000000000000000008';

    private const REASON            = 'Cliente solicitó cambio de fecha';

    private function makeTicketAssigned(): Ticket
    {
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);
        $ticket = Ticket::create(
            id: $ticketId,
            ticketNumber: 'TK-2026-000001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        );

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+1 hour'));

        return $ticket;
    }

    public function testRescheduleTicketSuccessfully(): void
    {
        $ticket = $this->makeTicketAssigned();

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $rescheduleRepo = $this->createMock(TicketRescheduleRepositoryInterface::class);
        $rescheduleRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::RESCHEDULE_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->atLeastOnce())->method('publish');

        $handler = new RescheduleTicketHandler($ticketRepo, $rescheduleRepo, $idGenerator, $eventPublisher);
        ($handler)(new RescheduleTicketCommand(
            ticketId: self::TICKET_ID,
            newScheduledAt: new \DateTimeImmutable('+2 days'),
            reason: self::REASON,
            requestedBy: RescheduleRequestedBy::CLIENT,
            rescheduledBy: self::RESCHEDULED_BY,
            slaMaxResponseHours: 4,
        ));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::ASSIGNED, $savedTicket->status());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $rescheduleRepo = $this->createMock(TicketRescheduleRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RescheduleTicketHandler($ticketRepo, $rescheduleRepo, $idGenerator, $eventPublisher);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new RescheduleTicketCommand(
            ticketId: self::TICKET_ID,
            newScheduledAt: new \DateTimeImmutable('+2 days'),
            reason: self::REASON,
            requestedBy: RescheduleRequestedBy::CLIENT,
            rescheduledBy: self::RESCHEDULED_BY,
        ));
    }

    public function testThrowsWhenTicketCannotBeRescheduled(): void
    {
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);
        $ticket = Ticket::create(
            id: $ticketId,
            ticketNumber: 'TK-2026-000001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        );

        // Assign to advance past CREATED into ASSIGNED
        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+1 hour'));

        // Advance to IN_TRANSIT_OUT (not reschedulable)
        $journey = \App\Support\Domain\Entities\Journey::start(
            id: \App\Support\Domain\ValueObjects\JourneyIdValueObject::fromString('01HZ0000000000000000000010'),
            ticketId: $ticketId,
            type: \App\Support\Domain\Enums\JourneyType::OUTWARD,
            isPartOfRoute: false,
            notes: null,
        );
        $ticket->startOutwardJourney($journey);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $rescheduleRepo = $this->createMock(TicketRescheduleRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::RESCHEDULE_ID);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RescheduleTicketHandler($ticketRepo, $rescheduleRepo, $idGenerator, $eventPublisher);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No se puede reprogramar');

        ($handler)(new RescheduleTicketCommand(
            ticketId: self::TICKET_ID,
            newScheduledAt: new \DateTimeImmutable('+2 days'),
            reason: self::REASON,
            requestedBy: RescheduleRequestedBy::CLIENT,
            rescheduledBy: self::RESCHEDULED_BY,
        ));
    }
}
