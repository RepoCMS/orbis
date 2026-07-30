<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\CreateRmaRecord\CreateRmaRecordCommand;
use App\Support\Application\Command\CreateRmaRecord\CreateRmaRecordHandler;
use App\Support\Domain\Repositories\RmaRecordRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class CreateRmaRecordHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const USER_ID     = '01HZ0000000000000000000002';

    public function testCreatesRmaRecordSuccessfully(): void
    {
        $ticket = \App\Support\Domain\Entities\Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000001',
            clientId: '01HZ0000000000000000000003',
            terminalAssignmentId: '01HZ0000000000000000000004',
            serviceType: \App\Support\Domain\Enums\ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::USER_ID,
        );

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $rmaRecordRepo = $this->createMock(RmaRecordRepositoryInterface::class);
        $rmaRecordRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01HZ0000000000000000000003');

        $handler = new CreateRmaRecordHandler($rmaRecordRepo, $ticketRepo, $idGenerator);
        $result = ($handler)(new CreateRmaRecordCommand(
            ticketId: self::TICKET_ID,
            productCode: 'PWB-MAIN-001',
            createdBy: self::USER_ID,
            serialNumber: 'SN-123456',
            quantity: 1,
            notes: 'RMA por garantía',
        ));

        self::assertSame('01HZ0000000000000000000003', $result);
    }
}
