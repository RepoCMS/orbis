<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\ListTicketPendingSpareParts\ListTicketPendingSparePartsHandler;
use App\Support\Application\Query\ListTicketPendingSpareParts\ListTicketPendingSparePartsQuery;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Support\Application\Service\SparePartViewScopeResolver;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketPendingSparePart;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketPendingSparePartRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPendingSparePartIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ListTicketPendingSparePartsHandlerTest extends TestCase
{
    private const TICKET_ID = '01HZ0000000000000000000001';
    private const EMPLOYEE  = '01HZ0000000000000000000009';
    private const CLIENT_ID = '01HZ000000000000000000000A';
    private const TERM_ID   = '01HZ000000000000000000000B';
    private const CREATED_BY = '01HZ000000000000000000000C';

    private function createScopeResolver(): SparePartViewScopeResolver
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        return new SparePartViewScopeResolver($employeeRepo, $locationRepo);
    }

    private function createScopedResolver(string $employeeId): SparePartViewScopeResolver
    {
        $employee = \App\Organization\Domain\Entities\Employee::hire(
            id: EmployeeIdValueObject::fromString($employeeId),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1234567890'),
            firstName: 'Pedro',
            lastName: 'Tecnico',
            email: new EmployeeEmailValueObject('juan@example.com'),
            phoneNumber: '0999000000',
            hiredAt: new DateTimeImmutable('2025-01-01'),
            status: EmployeeStatus::ACTIVE,
            profile: EmployeeProfile::TECHNICIAN,
        );

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findByEmployeeId')->willReturn([]);

        return new SparePartViewScopeResolver($employeeRepo, $locationRepo);
    }

    private function makeSparePart(string $id, string $productCode, int $quantity): TicketPendingSparePart
    {
        return TicketPendingSparePart::register(
            id: TicketPendingSparePartIdValueObject::fromString($id),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            productCode: $productCode,
            quantity: $quantity,
        );
    }

    private function makeTicket(): Ticket
    {
        return Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-0001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERM_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fault',
            createdBy: self::CREATED_BY,
        );
    }

    public function testReturnsPartsByTicketId(): void
    {
        $part1 = $this->makeSparePart('01HZ0000000000000000000002', 'PROD-A', 2);
        $part2 = $this->makeSparePart('01HZ0000000000000000000003', 'PROD-B', 1);

        $pendingRepo = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $pendingRepo->method('findByTicket')->willReturn([$part1, $part2]);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);

        $handler = new ListTicketPendingSparePartsHandler($pendingRepo, $ticketRepo, $this->createScopeResolver());
        $result = ($handler)(new ListTicketPendingSparePartsQuery(ticketId: self::TICKET_ID));

        self::assertCount(2, $result);
        self::assertSame('PROD-A', $result[0]->productCode);
        self::assertSame(2, $result[0]->quantity);
        self::assertSame('PROD-B', $result[1]->productCode);
        self::assertSame(1, $result[1]->quantity);
    }

    public function testReturnsPartsByEmployeeId(): void
    {
        $part = $this->makeSparePart('01HZ0000000000000000000004', 'PROD-C', 3);

        $pendingRepo = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $pendingRepo->method('findByTicket')->willReturn([$part]);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findByEmployeeId')->willReturn([$this->makeTicket()]);

        $handler = new ListTicketPendingSparePartsHandler($pendingRepo, $ticketRepo, $this->createScopeResolver());
        $result = ($handler)(new ListTicketPendingSparePartsQuery(employeeId: self::EMPLOYEE));

        self::assertCount(1, $result);
        self::assertSame('PROD-C', $result[0]->productCode);
    }

    public function testReturnsEmptyArrayWhenNoFilters(): void
    {
        $pendingRepo = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);

        $handler = new ListTicketPendingSparePartsHandler($pendingRepo, $ticketRepo, $this->createScopeResolver());
        $result = ($handler)(new ListTicketPendingSparePartsQuery());

        self::assertCount(0, $result);
    }

    public function testReturnsEmptyForEmployeeWithNoTickets(): void
    {
        $pendingRepo = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $pendingRepo->expects($this->never())->method('findByTicket');

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findByEmployeeId')->willReturn([]);

        $handler = new ListTicketPendingSparePartsHandler($pendingRepo, $ticketRepo, $this->createScopeResolver());
        $result = ($handler)(new ListTicketPendingSparePartsQuery(employeeId: self::EMPLOYEE));

        self::assertCount(0, $result);
    }

    public function testReturnsEmptyForTicketWithNoParts(): void
    {
        $pendingRepo = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $pendingRepo->method('findByTicket')->willReturn([]);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);

        $handler = new ListTicketPendingSparePartsHandler($pendingRepo, $ticketRepo, $this->createScopeResolver());
        $result = ($handler)(new ListTicketPendingSparePartsQuery(ticketId: self::TICKET_ID));

        self::assertCount(0, $result);
    }

    public function testReturnsScopedPartsByUserId(): void
    {
        $part = $this->makeSparePart('01HZ0000000000000000000004', 'PROD-C', 3);

        $pendingRepo = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $pendingRepo->method('findByTicket')->willReturn([$part]);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findByEmployeeId')->willReturn([$this->makeTicket()]);

        $handler = new ListTicketPendingSparePartsHandler($pendingRepo, $ticketRepo, $this->createScopedResolver(self::EMPLOYEE));
        $result = ($handler)(new ListTicketPendingSparePartsQuery(userId: '01HZ000000000000000000000D'));

        self::assertCount(1, $result);
        self::assertSame('PROD-C', $result[0]->productCode);
    }
}
