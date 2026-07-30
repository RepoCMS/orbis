<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Support\Application\Command\ApproveTicket\ApproveTicketCommand;
use App\Support\Application\Command\ApproveTicket\ApproveTicketHandler;
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

final class ApproveTicketHandlerTest extends TestCase
{
    private const TICKET_ID       = '01HZ0000000000000000000001';
    private const CLIENT_ID       = '01HZ0000000000000000000002';
    private const TERMINAL_ID     = '01HZ0000000000000000000003';
    private const CREATED_BY      = '01HZ0000000000000000000004';
    private const EMPLOYEE_ID_1   = '01HZ0000000000000000000005';
    private const ASSIGNMENT_ID_1 = '01HZ0000000000000000000006';
    private const APPROVED_BY     = '01HZ0000000000000000000007';
    private const CLIENT_CODE     = 'CLI';
    private const CITY_CODE       = 'GYE';
    private const TERMINAL_CODE   = 'ATM01';

    private function makeTicketPendingClosure(): Ticket
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

        $journey = \App\Support\Domain\Entities\Journey::start(
            id: JourneyIdValueObject::fromString('01HZ0000000000000000000010'),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
            isPartOfRoute: false,
            notes: null,
        );
        $ticket->startOutwardJourney($journey);
        $ticket->arriveAtSite();

        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString('01HZ0000000000000000000011'),
            ticketId: $ticketId,
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display no enciende',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::EMPLOYEE_ID_1,
        );
        $ticket->registerFinding($finding);

        // Complete assistance (IN_ASSISTANCE -> IN_TRANSIT_BACK)
        $ticket->completeAssistance();

        // Arrive at base (IN_TRANSIT_BACK -> PENDING_CLOSURE)
        $ticket->arriveAtBase();

        return $ticket;
    }

    public function testApprovesTicketSuccessfullyAndGeneratesCatCode(): void
    {
        $ticket = $this->makeTicketPendingClosure();

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->atLeast(1))
            ->method('publish');

        $handler = new ApproveTicketHandler($ticketRepo, $eventPublisher);
        ($handler)(new ApproveTicketCommand(
            ticketId: self::TICKET_ID,
            approvedBy: self::APPROVED_BY,
            clientCode: self::CLIENT_CODE,
            cityCode: self::CITY_CODE,
            terminalCode: self::TERMINAL_CODE,
        ));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::CLOSED, $savedTicket->status());
        self::assertNotNull($savedTicket->closedAt());
        self::assertNotNull($savedTicket->finding());
        self::assertNotNull($savedTicket->finding()->catCode());

        // Verify CAT code format: CLI-GYE-ATM01-YYYYMMDD-0001 (approx)
        $catCode = $savedTicket->finding()->catCode();
        self::assertStringStartsWith(self::CLIENT_CODE . '-' . self::CITY_CODE . '-' . self::TERMINAL_CODE . '-', $catCode);
        self::assertStringContainsString('-', $catCode);
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new ApproveTicketHandler($ticketRepo, $eventPublisher);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new ApproveTicketCommand(
            ticketId: self::TICKET_ID,
            approvedBy: self::APPROVED_BY,
            clientCode: self::CLIENT_CODE,
            cityCode: self::CITY_CODE,
            terminalCode: self::TERMINAL_CODE,
        ));
    }
}
