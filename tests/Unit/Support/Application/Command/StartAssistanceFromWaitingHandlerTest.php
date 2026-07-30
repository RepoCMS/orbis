<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Support\Application\Command\StartAssistanceFromWaiting\StartAssistanceFromWaitingCommand;
use App\Support\Application\Command\StartAssistanceFromWaiting\StartAssistanceFromWaitingHandler;
use App\Support\Domain\Entities\Journey;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Enums\JourneyType;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class StartAssistanceFromWaitingHandlerTest extends TestCase
{
    private const TICKET_ID       = '01HZ0000000000000000000001';
    private const CLIENT_ID       = '01HZ0000000000000000000002';
    private const TERMINAL_ID     = '01HZ0000000000000000000003';
    private const CREATED_BY      = '01HZ0000000000000000000004';
    private const ASSIGNMENT_ID   = '01HZ0000000000000000000005';
    private const EMPLOYEE_ID     = '01HZ0000000000000000000006';
    private const JOURNEY_ID      = '01HZ0000000000000000000007';

    private function makeTicketWaiting(): Ticket
    {
        $ticket = Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        );

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            employeeId: self::EMPLOYEE_ID,
            isInCharge: true,
        );

        $ticket->assign([$assignment], new \DateTimeImmutable('+1 hour'));

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            type: JourneyType::OUTWARD,
        );
        $ticket->startOutwardJourney($journey);
        $ticket->startWaiting();

        return $ticket;
    }

    public function testStartsAssistance(): void
    {
        $ticket = $this->makeTicketWaiting();

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $handler = new StartAssistanceFromWaitingHandler($ticketRepo);
        ($handler)(new StartAssistanceFromWaitingCommand(ticketId: self::TICKET_ID));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::IN_ASSISTANCE, $savedTicket->status());
        self::assertNotNull($savedTicket->assistanceStartAt());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);
        $ticketRepo->expects($this->never())->method('save');

        $handler = new StartAssistanceFromWaitingHandler($ticketRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new StartAssistanceFromWaitingCommand(ticketId: '01HZ0000000000000000000099'));
    }

    public function testThrowsWhenTicketNotInWaitingStatus(): void
    {
        $ticket = Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        );

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);
        $ticketRepo->expects($this->never())->method('save');

        $handler = new StartAssistanceFromWaitingHandler($ticketRepo);

        $this->expectException(\LogicException::class);

        ($handler)(new StartAssistanceFromWaitingCommand(ticketId: self::TICKET_ID));
    }
}
