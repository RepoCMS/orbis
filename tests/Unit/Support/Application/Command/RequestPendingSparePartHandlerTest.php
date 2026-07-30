<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\RequestPendingSparePart\RequestPendingSparePartCommand;
use App\Support\Application\Command\RequestPendingSparePart\RequestPendingSparePartHandler;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Application\Service\SparePartViewScopeResolver;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketPendingSparePart;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketAssignmentRepositoryInterface;
use App\Support\Domain\Repositories\TicketPendingSparePartRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class RequestPendingSparePartHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const CLIENT_ID   = '01HZ0000000000000000000002';
    private const TERMINAL_ID = '01HZ0000000000000000000003';
    private const CREATED_BY  = '01HZ0000000000000000000004';
    private const PENDING_ID  = '01HZ0000000000000000000005';
    private const USER_ID     = '01HZ0000000000000000000006';

    public function testRequestsSparePart(): void
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

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::PENDING_ID);

        $employeeRepo = $this->createMock(\App\Organization\Domain\Repositories\EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn(null);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $scopeResolver = new SparePartViewScopeResolver($employeeRepo, $locationRepo);

        $assignmentRepo = $this->createMock(TicketAssignmentRepositoryInterface::class);

        $savedEntity = null;
        $pendingRepo = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $pendingRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (TicketPendingSparePart $p) use (&$savedEntity): void {
                $savedEntity = $p;
            });

        $handler = new RequestPendingSparePartHandler($ticketRepo, $pendingRepo, $scopeResolver, $assignmentRepo, $idGenerator);
        ($handler)(new RequestPendingSparePartCommand(
            ticketId: self::TICKET_ID,
            productCode: 'PROD-001',
            quantity: 2,
            notes: 'Repuesto urgente',
            userId: self::USER_ID,
        ));

        self::assertNotNull($savedEntity);
        self::assertSame('PROD-001', $savedEntity->productCode());
        self::assertSame(2, $savedEntity->quantity());
        self::assertSame('Repuesto urgente', $savedEntity->notes());
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $pendingRepo = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $pendingRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $employeeRepo = $this->createMock(\App\Organization\Domain\Repositories\EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn(null);
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $scopeResolver = new SparePartViewScopeResolver($employeeRepo, $locationRepo);
        $assignmentRepo = $this->createMock(TicketAssignmentRepositoryInterface::class);

        $handler = new RequestPendingSparePartHandler($ticketRepo, $pendingRepo, $scopeResolver, $assignmentRepo, $idGenerator);

        $this->expectException(TicketNotFoundException::class);

        ($handler)(new RequestPendingSparePartCommand(
            ticketId: '01HZ0000000000000000000099',
            productCode: 'PROD-001',
            userId: self::USER_ID,
        ));
    }
}
