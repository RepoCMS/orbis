<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Support\Application\Command\RequestReopenTicket\RequestReopenTicketCommand;
use App\Support\Application\Command\RequestReopenTicket\RequestReopenTicketHandler;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class RequestReopenTicketHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const CLIENT_ID   = '01HZ0000000000000000000002';
    private const TERMINAL_ID = '01HZ0000000000000000000003';
    private const USER_ID     = '01HZ0000000000000000000004';

    public function testRequestsReopenSuccessfully(): void
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
        $ref->setValue($ticket, TicketStatus::CANCELLED);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RequestReopenTicketHandler($ticketRepo, $publisher);
        ($handler)(new RequestReopenTicketCommand(
            ticketId: self::TICKET_ID,
            reason: 'El problema persiste',
            requestedBy: self::USER_ID,
        ));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::PENDING_CLOSURE, $savedTicket->status());
        self::assertSame('El problema persiste', $savedTicket->reopenedReason());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RequestReopenTicketHandler($ticketRepo, $publisher);

        $this->expectException(TicketNotFoundException::class);

        ($handler)(new RequestReopenTicketCommand(
            ticketId: self::TICKET_ID,
            reason: 'El problema persiste',
            requestedBy: self::USER_ID,
        ));
    }
}
