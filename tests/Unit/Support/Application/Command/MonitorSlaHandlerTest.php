<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\MonitorSla\MonitorSlaCommand;
use App\Support\Application\Command\MonitorSla\MonitorSlaHandler;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Entities\TicketAuditLog;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Events\SlaAboutToExpire;
use App\Support\Domain\Events\SlaBreached;
use App\Support\Domain\Repositories\TicketAuditLogRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class MonitorSlaHandlerTest extends TestCase
{
    private const TICKET_ID       = '01HZ0000000000000000000001';
    private const CLIENT_ID       = '01HZ0000000000000000000002';
    private const TERMINAL_ID     = '01HZ0000000000000000000003';
    private const CREATED_BY      = '01HZ0000000000000000000004';
    private const ASSIGNMENT_ID   = '01HZ0000000000000000000005';
    private const EMPLOYEE_ID     = '01HZ0000000000000000000006';

    private function makeTicketWithSla(\DateTimeImmutable $slaDeadlineAt): Ticket
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

        $ticket->assign(
            newAssignments: [$assignment],
            scheduledAt: new \DateTimeImmutable('-1 hour'),
            slaMaxResponseHours: 4,
        );

        // Override slaDeadlineAt via reflection for precise testing
        $ref = new \ReflectionProperty(Ticket::class, 'slaDeadlineAt');
        $ref->setAccessible(true);
        $ref->setValue($ticket, $slaDeadlineAt);

        return $ticket;
    }

    public function testSlaAboutToExpire(): void
    {
        $slaDeadline = new \DateTimeImmutable('+50 minutes');
        $ticket = $this->makeTicketWithSla($slaDeadline);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findByStatuses')->willReturn([$ticket]);

        $auditLogRepo = $this->createMock(TicketAuditLogRepositoryInterface::class);
        $auditLogRepo->method('findByTicketId')->willReturn([]);
        $auditLogRepo->expects($this->once())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01HZ0000000000000000000007');

        $handler = new MonitorSlaHandler($ticketRepo, $auditLogRepo, $eventPublisher, $idGenerator);
        $result = ($handler)(new MonitorSlaCommand());

        self::assertSame(1, $result->totalTickets);
        self::assertSame(1, $result->aboutToExpireAlerts);
        self::assertSame(0, $result->criticalAlerts);
        self::assertSame(0, $result->breachedAlerts);
    }

    public function testSlaBreached(): void
    {
        $slaDeadline = new \DateTimeImmutable('-5 minutes');
        $ticket = $this->makeTicketWithSla($slaDeadline);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findByStatuses')->willReturn([$ticket]);

        $auditLogRepo = $this->createMock(TicketAuditLogRepositoryInterface::class);
        $auditLogRepo->method('findByTicketId')->willReturn([]);
        $auditLogRepo->expects($this->once())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01HZ0000000000000000000007');

        $handler = new MonitorSlaHandler($ticketRepo, $auditLogRepo, $eventPublisher, $idGenerator);
        $result = ($handler)(new MonitorSlaCommand());

        self::assertSame(1, $result->totalTickets);
        self::assertSame(0, $result->aboutToExpireAlerts);
        self::assertSame(0, $result->criticalAlerts);
        self::assertSame(1, $result->breachedAlerts);
    }

    public function testNoActionNeeded(): void
    {
        $slaDeadline = new \DateTimeImmutable('+3 hours');
        $ticket = $this->makeTicketWithSla($slaDeadline);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findByStatuses')->willReturn([$ticket]);

        $auditLogRepo = $this->createMock(TicketAuditLogRepositoryInterface::class);
        $auditLogRepo->method('findByTicketId')->willReturn([]);
        $auditLogRepo->expects($this->never())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->never())->method('publish');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new MonitorSlaHandler($ticketRepo, $auditLogRepo, $eventPublisher, $idGenerator);
        $result = ($handler)(new MonitorSlaCommand());

        self::assertSame(1, $result->totalTickets);
        self::assertSame(0, $result->totalAlerts());
    }

    public function testSkipsTicketWithNoSlaDeadline(): void
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
        $ticketRepo->method('findByStatuses')->willReturn([$ticket]);

        $auditLogRepo = $this->createMock(TicketAuditLogRepositoryInterface::class);
        $auditLogRepo->expects($this->never())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->never())->method('publish');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new MonitorSlaHandler($ticketRepo, $auditLogRepo, $eventPublisher, $idGenerator);
        $result = ($handler)(new MonitorSlaCommand());

        self::assertSame(1, $result->totalTickets);
        self::assertSame(0, $result->totalAlerts());
    }
}
