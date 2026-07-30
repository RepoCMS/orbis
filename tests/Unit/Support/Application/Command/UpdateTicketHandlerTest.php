<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Support\Application\Command\UpdateTicket\UpdateTicketCommand;
use App\Support\Application\Command\UpdateTicket\UpdateTicketHandler;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateTicketHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const CLIENT_ID   = '01HZ0000000000000000000002';
    private const TERMINAL_ID = '01HZ0000000000000000000003';
    private const USER_ID     = '01HZ0000000000000000000004';

    public function testUpdatesDescriptionSuccessfully(): void
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

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $handler = new UpdateTicketHandler($ticketRepo);
        ($handler)(new UpdateTicketCommand(
            ticketId: self::TICKET_ID,
            description: 'ATM fuera de servicio - pantalla dañada',
        ));

        self::assertNotNull($savedTicket);
        self::assertSame('ATM fuera de servicio - pantalla dañada', $savedTicket->description());
    }

    public function testUpdatesServiceTypeSuccessfully(): void
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

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $handler = new UpdateTicketHandler($ticketRepo);
        ($handler)(new UpdateTicketCommand(
            ticketId: self::TICKET_ID,
            serviceType: ServiceType::MANTENIMIENTO,
        ));

        self::assertNotNull($savedTicket);
        self::assertSame(ServiceType::MANTENIMIENTO, $savedTicket->serviceType());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $handler = new UpdateTicketHandler($ticketRepo);

        $this->expectException(TicketNotFoundException::class);

        ($handler)(new UpdateTicketCommand(
            ticketId: self::TICKET_ID,
            description: 'ATM fuera de servicio',
        ));
    }
}
