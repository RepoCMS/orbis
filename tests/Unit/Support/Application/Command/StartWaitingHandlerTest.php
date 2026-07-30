<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Support\Application\Command\StartWaiting\StartWaitingCommand;
use App\Support\Application\Command\StartWaiting\StartWaitingHandler;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class StartWaitingHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const CLIENT_ID   = '01HZ0000000000000000000002';
    private const TERMINAL_ID = '01HZ0000000000000000000003';
    private const USER_ID     = '01HZ0000000000000000000004';

    public function testStartsWaitingSuccessfully(): void
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

        $ref = new \ReflectionProperty(Ticket::class, 'status');
        $ref->setValue($ticket, \App\Support\Domain\Enums\TicketStatus::IN_TRANSIT_OUT);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $handler = new StartWaitingHandler($ticketRepo);
        ($handler)(new StartWaitingCommand(ticketId: self::TICKET_ID));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::WAITING, $savedTicket->status());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $handler = new StartWaitingHandler($ticketRepo);

        $this->expectException(\InvalidArgumentException::class);

        ($handler)(new StartWaitingCommand(ticketId: self::TICKET_ID));
    }
}
