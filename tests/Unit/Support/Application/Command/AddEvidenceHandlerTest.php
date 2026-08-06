<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\AddEvidence\AddEvidenceCommand;
use App\Support\Application\Command\AddEvidence\AddEvidenceHandler;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class AddEvidenceHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const CLIENT_ID   = '01HZ0000000000000000000002';
    private const TERMINAL_ID = '01HZ0000000000000000000003';
    private const USER_ID     = '01HZ0000000000000000000004';

    public function testAddsEvidenceSuccessfully(): void
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

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01HZ0000000000000000000005');

        $handler = new AddEvidenceHandler($ticketRepo, $idGenerator);
        ($handler)(new AddEvidenceCommand(
            ticketId: self::TICKET_ID,
            filePath: '/tmp/evidence.jpg',
            uploadedBy: self::USER_ID,
        ));

        self::assertNotNull($savedTicket);
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new AddEvidenceHandler($ticketRepo, $idGenerator);

        $this->expectException(TicketNotFoundException::class);

        ($handler)(new AddEvidenceCommand(
            ticketId: self::TICKET_ID,
            filePath: '/tmp/evidence.jpg',
            uploadedBy: self::USER_ID,
        ));
    }
}
