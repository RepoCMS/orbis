<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\ReassignTicket\ReassignTicketCommand;
use App\Support\Application\Command\ReassignTicket\ReassignTicketHandler;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketAssignmentRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class ReassignTicketHandlerTest extends TestCase
{
    private const TICKET_ID    = '01HZ0000000000000000000001';
    private const CLIENT_ID    = '01HZ0000000000000000000002';
    private const TERMINAL_ID  = '01HZ0000000000000000000003';
    private const USER_ID      = '01HZ0000000000000000000004';
    private const OLD_EMPLOYEE = '01HZ0000000000000000000005';
    private const NEW_EMPLOYEE = '01HZ0000000000000000000006';

    public function testReassignsTicketSuccessfully(): void
    {
        $ticket = Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::USER_ID,
        );

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $assignmentRepo = $this->createMock(TicketAssignmentRepositoryInterface::class);
        $assignmentRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01HZ0000000000000000000007');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->atLeast(1))->method('publish');

        $handler = new ReassignTicketHandler($ticketRepo, $assignmentRepo, $idGenerator, $publisher);
        ($handler)(new ReassignTicketCommand(
            ticketId: self::TICKET_ID,
            newEmployeeId: self::NEW_EMPLOYEE,
            previousEmployeeId: self::OLD_EMPLOYEE,
            reassignedBy: self::USER_ID,
        ));

        self::assertTrue(true);
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $assignmentRepo = $this->createMock(TicketAssignmentRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new ReassignTicketHandler($ticketRepo, $assignmentRepo, $idGenerator, $publisher);

        $this->expectException(TicketNotFoundException::class);

        ($handler)(new ReassignTicketCommand(
            ticketId: self::TICKET_ID,
            newEmployeeId: self::NEW_EMPLOYEE,
            previousEmployeeId: self::OLD_EMPLOYEE,
            reassignedBy: self::USER_ID,
        ));
    }
}
