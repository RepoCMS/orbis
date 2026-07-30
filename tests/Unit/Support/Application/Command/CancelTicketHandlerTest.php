<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Support\Application\Command\CancelTicket\CancelTicketCommand;
use App\Support\Application\Command\CancelTicket\CancelTicketHandler;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class CancelTicketHandlerTest extends TestCase
{
    private const TICKET_ID  = '01HZ0000000000000000000001';
    private const CLIENT_ID  = '01HZ0000000000000000000002';
    private const TERMINAL_ID = '01HZ0000000000000000000003';
    private const CREATED_BY = '01HZ0000000000000000000004';
    private const CANCEL_BY  = '01HZ0000000000000000000005';

    public function testCancelsTicketSuccessfully(): void
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

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->atLeast(1))->method('publish');

        $handler = new CancelTicketHandler($ticketRepo, $eventPublisher);
        ($handler)(new CancelTicketCommand(
            ticketId: self::TICKET_ID,
            reason: 'Cliente canceló solicitud',
            cancelledBy: self::CANCEL_BY,
        ));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::CANCELLED, $savedTicket->status());
        self::assertNotNull($savedTicket->cancelledAt());
        self::assertSame('Cliente canceló solicitud', $savedTicket->cancellationReason());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new CancelTicketHandler($ticketRepo, $eventPublisher);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new CancelTicketCommand(
            ticketId: self::TICKET_ID,
            reason: 'Cliente canceló solicitud',
            cancelledBy: self::CANCEL_BY,
        ));
    }

    public function testThrowsWhenTicketInTerminalState(): void
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

        $ticket->cancel('Cancelación inicial', self::CANCEL_BY);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new CancelTicketHandler($ticketRepo, $eventPublisher);

        $this->expectException(\LogicException::class);

        ($handler)(new CancelTicketCommand(
            ticketId: self::TICKET_ID,
            reason: 'Intentar cancelar de nuevo',
            cancelledBy: self::CANCEL_BY,
        ));
    }
}
