<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\StartOutwardJourney\StartOutwardJourneyCommand;
use App\Support\Application\Command\StartOutwardJourney\StartOutwardJourneyHandler;
use App\Support\Domain\Entities\Journey;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Enums\JourneyType;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\JourneyRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class StartOutwardJourneyHandlerTest extends TestCase
{
    private const TICKET_ID       = '01HZ0000000000000000000001';
    private const CLIENT_ID       = '01HZ0000000000000000000002';
    private const TERMINAL_ID     = '01HZ0000000000000000000003';
    private const CREATED_BY      = '01HZ0000000000000000000004';
    private const EMPLOYEE_ID_1   = '01HZ0000000000000000000005';
    private const ASSIGNMENT_ID_1 = '01HZ0000000000000000000006';
    private const JOURNEY_ID      = '01HZ0000000000000000000007';

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

    public function testStartsOutwardJourneySuccessfully(): void
    {
        $ticket = $this->makeTicketAssigned();

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::JOURNEY_ID);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $journeyRepo = $this->createMock(JourneyRepositoryInterface::class);
        $savedJourney = null;
        $journeyRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Journey $j) use (&$savedJourney): void {
                $savedJourney = $j;
            });

        $handler = new StartOutwardJourneyHandler($ticketRepo, $journeyRepo, $idGenerator);
        ($handler)(new StartOutwardJourneyCommand(
            ticketId: self::TICKET_ID,
        ));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::IN_TRANSIT_OUT, $savedTicket->status());
        self::assertNotNull($savedTicket->inTransitOutAt());
        self::assertCount(1, $savedTicket->journeys());
        self::assertNotNull($savedJourney);
        self::assertSame(JourneyType::OUTWARD, $savedJourney->type());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $journeyRepo = $this->createMock(JourneyRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new StartOutwardJourneyHandler($ticketRepo, $journeyRepo, $idGenerator);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new StartOutwardJourneyCommand(
            ticketId: self::TICKET_ID,
        ));
    }

    public function testThrowsWhenTicketNotAssigned(): void
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

        $journeyRepo = $this->createMock(JourneyRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::JOURNEY_ID);

        $handler = new StartOutwardJourneyHandler($ticketRepo, $journeyRepo, $idGenerator);

        $this->expectException(\LogicException::class);

        ($handler)(new StartOutwardJourneyCommand(
            ticketId: self::TICKET_ID,
        ));
    }
}
