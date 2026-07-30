<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\ListTickets\ListTicketsHandler;
use App\Support\Application\Query\ListTickets\ListTicketsQuery;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListTicketsHandlerTest extends TestCase
{
    private const TICKET_ID_1  = '01HZ0000000000000000000001';
    private const TICKET_ID_2  = '01HZ0000000000000000000002';
    private const CLIENT_ID    = '01HZ0000000000000000000003';
    private const TERMINAL_ID  = '01HZ0000000000000000000004';
    private const CREATED_BY   = '01HZ0000000000000000000005';

    private function makeTicket(string $id, ServiceType $serviceType): Ticket
    {
        return Ticket::create(
            id: TicketIdValueObject::fromString($id),
            ticketNumber: 'TK-2026-00000' . substr($id, -1),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: $serviceType,
            description: 'Test ticket',
            createdBy: self::CREATED_BY,
        );
    }

    public function testReturnsTickets(): void
    {
        $tickets = [
            $this->makeTicket(self::TICKET_ID_1, ServiceType::CORRECTIVO),
            $this->makeTicket(self::TICKET_ID_2, ServiceType::MANTENIMIENTO),
        ];

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findAll')->willReturn($tickets);

        $handler = new ListTicketsHandler($ticketRepo);
        $result = ($handler)(new ListTicketsQuery());

        self::assertCount(2, $result);
        self::assertSame('TK-2026-000001', $result[0]->ticketNumber);
        self::assertSame('TK-2026-000002', $result[1]->ticketNumber);
    }

    public function testReturnsEmptyWhenNoTickets(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findAll')->willReturn([]);

        $handler = new ListTicketsHandler($ticketRepo);
        $result = ($handler)(new ListTicketsQuery());

        self::assertCount(0, $result);
    }

    public function testFiltersByClientId(): void
    {
        $ticket = $this->makeTicket(self::TICKET_ID_1, ServiceType::CORRECTIVO);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findByClientId')->willReturn([$ticket]);

        $handler = new ListTicketsHandler($ticketRepo);
        $result = ($handler)(new ListTicketsQuery(clientId: self::CLIENT_ID));

        self::assertCount(1, $result);
        self::assertSame(self::CLIENT_ID, $result[0]->clientId);
    }

    public function testFiltersByStatus(): void
    {
        $ticket = $this->makeTicket(self::TICKET_ID_1, ServiceType::CORRECTIVO);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findByStatuses')->willReturn([$ticket]);

        $handler = new ListTicketsHandler($ticketRepo);
        $result = ($handler)(new ListTicketsQuery(
            statuses: [TicketStatus::CREATED],
        ));

        self::assertCount(1, $result);
        self::assertSame(TicketStatus::CREATED->value, $result[0]->status);
    }
}
