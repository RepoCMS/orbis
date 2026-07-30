<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Support\Application\Command\ArriveAtSite\ArriveAtSiteCommand;
use App\Support\Application\Command\ArriveAtSite\ArriveAtSiteHandler;
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

final class ArriveAtSiteHandlerTest extends TestCase
{
    private const TICKET_ID       = '01HZ0000000000000000000001';
    private const CLIENT_ID       = '01HZ0000000000000000000002';
    private const TERMINAL_ID     = '01HZ0000000000000000000003';
    private const CREATED_BY      = '01HZ0000000000000000000004';
    private const EMPLOYEE_ID_1   = '01HZ0000000000000000000005';
    private const ASSIGNMENT_ID_1 = '01HZ0000000000000000000006';
    private const JOURNEY_ID_1    = '01HZ0000000000000000000007';

    private function makeTicketInTransitOut(): Ticket
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

        return $ticket;
    }

    public function testArriveAtSiteSuccessfully(): void
    {
        $ticket = $this->makeTicketInTransitOut();

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $handler = new ArriveAtSiteHandler($ticketRepo);
        ($handler)(new ArriveAtSiteCommand(ticketId: self::TICKET_ID));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::IN_ASSISTANCE, $savedTicket->status());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $handler = new ArriveAtSiteHandler($ticketRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new ArriveAtSiteCommand(ticketId: self::TICKET_ID));
    }

    public function testThrowsWhenTicketNotInTransitOut(): void
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

        $handler = new ArriveAtSiteHandler($ticketRepo);

        $this->expectException(\LogicException::class);

        ($handler)(new ArriveAtSiteCommand(ticketId: self::TICKET_ID));
    }
}
