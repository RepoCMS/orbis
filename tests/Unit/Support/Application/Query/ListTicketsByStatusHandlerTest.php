<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\ListTicketsByStatus\ListTicketsByStatusHandler;
use App\Support\Application\Query\ListTicketsByStatus\ListTicketsByStatusQuery;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListTicketsByStatusHandlerTest extends TestCase
{
    private const CLIENT_ID  = '01HZ0000000000000000000001';
    private const TERMINAL   = '01HZ0000000000000000000002';
    private const CREATED_BY = '01HZ0000000000000000000003';

    public function testReturnsTicketsByStatus(): void
    {
        $ticket = Ticket::create(
            id: TicketIdValueObject::fromString('01HZ000000000000000000000A'),
            ticketNumber: 'TK-2026-000001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL,
            serviceType: ServiceType::CORRECTIVO,
            description: 'Ticket en estado created',
            createdBy: self::CREATED_BY,
        );

        $repo = $this->createMock(TicketRepositoryInterface::class);
        $repo->method('findByStatuses')->willReturn([$ticket]);

        $handler = new ListTicketsByStatusHandler($repo);
        $result = ($handler)(new ListTicketsByStatusQuery(status: TicketStatus::CREATED));

        self::assertCount(1, $result);
        self::assertSame(TicketStatus::CREATED->value, $result[0]->status);
        self::assertSame('TK-2026-000001', $result[0]->ticketNumber);
    }

    public function testReturnsEmptyArrayWhenNoTickets(): void
    {
        $repo = $this->createMock(TicketRepositoryInterface::class);
        $repo->method('findByStatuses')->willReturn([]);

        $handler = new ListTicketsByStatusHandler($repo);
        $result = ($handler)(new ListTicketsByStatusQuery(status: TicketStatus::CLOSED));

        self::assertCount(0, $result);
    }
}
