<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\RegisterSparePart\RegisterSparePartCommand;
use App\Support\Application\Command\RegisterSparePart\RegisterSparePartHandler;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Application\Service\SparePartViewScopeResolver;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketAssignmentRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\Repositories\TicketSparePartRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class RegisterSparePartHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const FINDING_ID  = '01HZ0000000000000000000002';
    private const CLIENT_ID   = '01HZ0000000000000000000003';
    private const TERMINAL_ID = '01HZ0000000000000000000004';
    private const USER_ID     = '01HZ0000000000000000000005';

    public function testRegistersSparePartSuccessfully(): void
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

        $sparePartRepo = $this->createMock(TicketSparePartRepositoryInterface::class);
        $sparePartRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01HZ0000000000000000000006');

        $assignmentRepo = $this->createMock(TicketAssignmentRepositoryInterface::class);
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $scopeResolver = new SparePartViewScopeResolver($employeeRepo, $locationRepo);

        $handler = new RegisterSparePartHandler(
            $ticketRepo,
            $sparePartRepo,
            $assignmentRepo,
            $scopeResolver,
            $employeeRepo,
            $idGenerator,
        );
        ($handler)(new RegisterSparePartCommand(
            ticketId: self::TICKET_ID,
            findingId: self::FINDING_ID,
            productCode: 'PWB-MAIN-001',
            userId: self::USER_ID,
            serialNumber: 'SN-123456',
            quantity: 1,
            isIncoming: true,
            isVisibleToClient: true,
        ));

        self::assertTrue(true);
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $sparePartRepo = $this->createMock(TicketSparePartRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $assignmentRepo = $this->createMock(TicketAssignmentRepositoryInterface::class);
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $scopeResolver = new SparePartViewScopeResolver($employeeRepo, $locationRepo);

        $handler = new RegisterSparePartHandler(
            $ticketRepo,
            $sparePartRepo,
            $assignmentRepo,
            $scopeResolver,
            $employeeRepo,
            $idGenerator,
        );

        $this->expectException(TicketNotFoundException::class);

        ($handler)(new RegisterSparePartCommand(
            ticketId: self::TICKET_ID,
            findingId: self::FINDING_ID,
            productCode: 'PWB-MAIN-001',
            userId: self::USER_ID,
        ));
    }
}
