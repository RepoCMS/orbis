<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Support\Application\Command\ArriveAtBase\ArriveAtBaseCommand;
use App\Support\Application\Command\ArriveAtBase\ArriveAtBaseHandler;
use App\Support\Domain\Entities\Journey;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Entities\TicketFinding;
use App\Support\Domain\Enums\JourneyType;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class ArriveAtBaseHandlerTest extends TestCase
{
    private const TICKET_ID       = '01HZ0000000000000000000001';
    private const CLIENT_ID       = '01HZ0000000000000000000002';
    private const TERMINAL_ID     = '01HZ0000000000000000000003';
    private const CREATED_BY      = '01HZ0000000000000000000004';
    private const EMPLOYEE_ID_1   = '01HZ0000000000000000000005';
    private const ASSIGNMENT_ID_1 = '01HZ0000000000000000000006';
    private const JOURNEY_ID_1    = '01HZ0000000000000000000007';
    private const FINDING_ID_1    = '01HZ0000000000000000000008';

    private function makeTicketInTransitBack(): Ticket
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

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
            isPartOfRoute: false,
            notes: null,
        );
        $ticket->startOutwardJourney($journey);
        $ticket->arriveAtSite();

        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: $ticketId,
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display no enciende',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::EMPLOYEE_ID_1,
        );
        $ticket->registerFinding($finding);
        $ticket->completeAssistance();

        return $ticket;
    }

    public function testArriveAtBaseSuccessfully(): void
    {
        $ticket = $this->makeTicketInTransitBack();

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $handler = new ArriveAtBaseHandler($ticketRepo);
        ($handler)(new ArriveAtBaseCommand(ticketId: self::TICKET_ID));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::PENDING_CLOSURE, $savedTicket->status());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $handler = new ArriveAtBaseHandler($ticketRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new ArriveAtBaseCommand(ticketId: self::TICKET_ID));
    }

    public function testThrowsWhenTicketNotInTransitBack(): void
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

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $handler = new ArriveAtBaseHandler($ticketRepo);

        $this->expectException(\LogicException::class);

        ($handler)(new ArriveAtBaseCommand(ticketId: self::TICKET_ID));
    }
}
