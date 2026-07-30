<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\GetTicketDetail\GetTicketDetailHandler;
use App\Support\Application\Query\GetTicketDetail\GetTicketDetailQuery;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class GetTicketDetailHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const CLIENT_ID   = '01HZ0000000000000000000002';
    private const TERMINAL_ID = '01HZ0000000000000000000003';
    private const CREATED_BY  = '01HZ0000000000000000000004';

    public function testReturnsDetail(): void
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

        $handler = new GetTicketDetailHandler($ticketRepo);
        $result = ($handler)(new GetTicketDetailQuery(ticketId: self::TICKET_ID));

        self::assertSame(self::TICKET_ID, $result->id);
        self::assertSame('TK-2026-000001', $result->ticketNumber);
        self::assertSame(self::CLIENT_ID, $result->clientId);
        self::assertSame(ServiceType::CORRECTIVO->value, $result->serviceType);
        self::assertSame(TicketStatus::CREATED->value, $result->status);
        self::assertSame('ATM fuera de servicio', $result->description);
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $handler = new GetTicketDetailHandler($ticketRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new GetTicketDetailQuery(ticketId: '01HZ0000000000000000000099'));
    }
}
