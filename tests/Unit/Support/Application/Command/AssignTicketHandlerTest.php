<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\AssignTicket\AssignTicketCommand;
use App\Support\Application\Command\AssignTicket\AssignTicketHandler;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class AssignTicketHandlerTest extends TestCase
{
    private const TICKET_ID       = '01HZ0000000000000000000001';
    private const CLIENT_ID       = '01HZ0000000000000000000002';
    private const TERMINAL_ID     = '01HZ0000000000000000000003';
    private const CREATED_BY      = '01HZ0000000000000000000004';
    private const ASSIGNMENT_ID_1 = '01HZ0000000000000000000005';
    private const ASSIGNMENT_ID_2 = '01HZ0000000000000000000006';
    private const EMPLOYEE_ID_1   = '01HZ0000000000000000000007';
    private const EMPLOYEE_ID_2   = '01HZ0000000000000000000008';
    private const IN_CHARGE_ID    = '01HZ0000000000000000000007';

    private function makeTicketCreated(): Ticket
    {
        return Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        );
    }

    public function testAssignsTicketSuccessfully(): void
    {
        $ticket = $this->makeTicketCreated();

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(self::ASSIGNMENT_ID_1, self::ASSIGNMENT_ID_2);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->atLeast(1))->method('publish');

        $handler = new AssignTicketHandler($ticketRepo, $idGenerator, $eventPublisher);
        ($handler)(new AssignTicketCommand(
            ticketId: self::TICKET_ID,
            employeeIds: [self::EMPLOYEE_ID_1, self::EMPLOYEE_ID_2],
            inChargeId: self::IN_CHARGE_ID,
            scheduledAt: new \DateTimeImmutable('+1 hour'),
            slaMaxResponseHours: 4,
        ));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::ASSIGNED, $savedTicket->status());
        self::assertCount(2, $savedTicket->assignments());
        self::assertNotNull($savedTicket->scheduledAt());
        self::assertNotNull($savedTicket->slaDeadlineAt());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new AssignTicketHandler($ticketRepo, $idGenerator, $eventPublisher);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new AssignTicketCommand(
            ticketId: self::TICKET_ID,
            employeeIds: [self::EMPLOYEE_ID_1],
            inChargeId: self::EMPLOYEE_ID_1,
            scheduledAt: null,
        ));
    }

    public function testThrowsWhenTicketNotCreated(): void
    {
        $ticket = $this->makeTicketCreated();

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+1 hour'));

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::ASSIGNMENT_ID_2);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new AssignTicketHandler($ticketRepo, $idGenerator, $eventPublisher);

        $this->expectException(\LogicException::class);

        ($handler)(new AssignTicketCommand(
            ticketId: self::TICKET_ID,
            employeeIds: [self::EMPLOYEE_ID_1],
            inChargeId: self::EMPLOYEE_ID_1,
            scheduledAt: null,
        ));
    }
}
