<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Support\Application\Command\ApproveReopenTicket\ApproveReopenTicketCommand;
use App\Support\Application\Command\ApproveReopenTicket\ApproveReopenTicketHandler;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class ApproveReopenTicketHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const CLIENT_ID   = '01HZ0000000000000000000002';
    private const TERMINAL_ID = '01HZ0000000000000000000003';
    private const USER_ID     = '01HZ0000000000000000000004';
    private const APPROVER_ID = '01HZ0000000000000000000005';

    public function testApprovesReopenSuccessfully(): void
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
        $ref->setValue($ticket, TicketStatus::PENDING_CLOSURE);
        $ref2 = new \ReflectionProperty(Ticket::class, 'reopenedReason');
        $ref2->setValue($ticket, 'El problema persiste');

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->atLeast(1))->method('publish');

        $handler = new ApproveReopenTicketHandler($ticketRepo, $publisher);
        ($handler)(new ApproveReopenTicketCommand(
            ticketId: self::TICKET_ID,
            approvedBy: self::APPROVER_ID,
        ));

        self::assertNotNull($savedTicket);
        self::assertSame(TicketStatus::REOPENED, $savedTicket->status());
        self::assertNotNull($savedTicket->reopenedAt());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new ApproveReopenTicketHandler($ticketRepo, $publisher);

        $this->expectException(TicketNotFoundException::class);

        ($handler)(new ApproveReopenTicketCommand(
            ticketId: self::TICKET_ID,
            approvedBy: self::APPROVER_ID,
        ));
    }
}
