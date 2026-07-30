<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\CreateTicket\CreateTicketCommand;
use App\Support\Application\Command\CreateTicket\CreateTicketHandler;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CreateTicketHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const CLIENT_ID   = '01HZ0000000000000000000002';
    private const TERMINAL_ID = '01HZ0000000000000000000003';
    private const CREATED_BY  = '01HZ0000000000000000000004';

    public function testCreatesTicketSuccessfully(): void
    {
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::TICKET_ID);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('nextTicketSequence')->willReturn(1);

        $savedTicket = null;
        $ticketRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Ticket $t) use (&$savedTicket): void {
                $savedTicket = $t;
            });

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->atLeast(1))->method('publish');

        $handler = new CreateTicketHandler($ticketRepo, $idGenerator, $eventPublisher);
        $result = ($handler)(new CreateTicketCommand(
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        ));

        self::assertSame(self::TICKET_ID, $result);
        self::assertNotNull($savedTicket);
        self::assertSame(self::CLIENT_ID, $savedTicket->clientId());
        self::assertSame(self::TERMINAL_ID, $savedTicket->terminalAssignmentId());
        self::assertSame(ServiceType::CORRECTIVO, $savedTicket->serviceType());
        self::assertSame('TK-2026-000001', $savedTicket->ticketNumber());
    }

    public function testThrowsWhenDescriptionEmpty(): void
    {
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::TICKET_ID);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('nextTicketSequence')->willReturn(1);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new CreateTicketHandler($ticketRepo, $idGenerator, $eventPublisher);

        $this->expectException(\InvalidArgumentException::class);

        ($handler)(new CreateTicketCommand(
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: '',
            createdBy: self::CREATED_BY,
        ));
    }

    public function testThrowsWhenClientIdEmpty(): void
    {
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::TICKET_ID);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('nextTicketSequence')->willReturn(1);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new CreateTicketHandler($ticketRepo, $idGenerator, $eventPublisher);

        $this->expectException(\InvalidArgumentException::class);

        ($handler)(new CreateTicketCommand(
            clientId: '',
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        ));
    }
}
