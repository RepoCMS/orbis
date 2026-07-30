<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\RegisterFinding\RegisterFindingCommand;
use App\Support\Application\Command\RegisterFinding\RegisterFindingHandler;
use App\Support\Domain\Entities\Journey;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Entities\TicketFinding;
use App\Support\Domain\Enums\JourneyType;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketFindingRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class RegisterFindingHandlerTest extends TestCase
{
    private const TICKET_ID       = '01HZ0000000000000000000001';
    private const CLIENT_ID       = '01HZ0000000000000000000002';
    private const TERMINAL_ID     = '01HZ0000000000000000000003';
    private const CREATED_BY      = '01HZ0000000000000000000004';
    private const EMPLOYEE_ID_1   = '01HZ0000000000000000000005';
    private const ASSIGNMENT_ID_1 = '01HZ0000000000000000000006';
    private const JOURNEY_ID      = '01HZ0000000000000000000007';
    private const FINDING_ID      = '01HZ0000000000000000000008';

    private function makeTicketInAssistance(): Ticket
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
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
            isPartOfRoute: false,
            notes: null,
        );
        $ticket->startOutwardJourney($journey);
        $ticket->arriveAtSite();

        return $ticket;
    }

    public function testRegistersFindingSuccessfully(): void
    {
        $ticket = $this->makeTicketInAssistance();

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::FINDING_ID);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $findingRepo = $this->createMock(TicketFindingRepositoryInterface::class);
        $savedFinding = null;
        $findingRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (TicketFinding $f) use (&$savedFinding): void {
                $savedFinding = $f;
            });

        $handler = new RegisterFindingHandler($ticketRepo, $findingRepo, $idGenerator);
        ($handler)(new RegisterFindingCommand(
            ticketId: self::TICKET_ID,
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display no enciende',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::EMPLOYEE_ID_1,
            physicalDocNumber: 'DOC-001',
        ));

        self::assertNotNull($savedTicket);
        self::assertNotNull($savedFinding);
        self::assertNotNull($savedTicket->finding());
        self::assertSame('FALLA_DISPLAY', $savedFinding->problemTypeFound());
        self::assertSame('Display no enciende', $savedFinding->problemDetail());
        self::assertTrue($savedFinding->batteryVoltageOk());
        self::assertSame('Reemplazo de display', $savedFinding->solution());
        self::assertSame(self::EMPLOYEE_ID_1, $savedFinding->registeredBy());
        self::assertSame('DOC-001', $savedFinding->physicalDocNumber());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $findingRepo = $this->createMock(TicketFindingRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new RegisterFindingHandler($ticketRepo, $findingRepo, $idGenerator);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new RegisterFindingCommand(
            ticketId: self::TICKET_ID,
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display no enciende',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::EMPLOYEE_ID_1,
        ));
    }

    public function testThrowsWhenTicketNotInAssistance(): void
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

        $findingRepo = $this->createMock(TicketFindingRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::FINDING_ID);

        $handler = new RegisterFindingHandler($ticketRepo, $findingRepo, $idGenerator);

        $this->expectException(\LogicException::class);

        ($handler)(new RegisterFindingCommand(
            ticketId: self::TICKET_ID,
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display no enciende',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::EMPLOYEE_ID_1,
        ));
    }
}
