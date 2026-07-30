<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query\ListTicketSpareParts;

use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Support\Application\Query\ListTicketSpareParts\ListTicketSparePartsHandler;
use App\Support\Application\Query\ListTicketSpareParts\ListTicketSparePartsQuery;
use App\Support\Application\Service\SparePartViewScopeResolver;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketSparePart;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\Repositories\TicketSparePartRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketSparePartIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ListTicketSparePartsHandlerTest extends TestCase
{
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
            email: new EmployeeEmailValueObject('pedro@example.com'),
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
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const TICKET_ID_2 = '01HZ0000000000000000000002';
    private const FINDING_ID  = '01HZ0000000000000000000003';
    private const FINDING_ID_2= '01HZ0000000000000000000004';
    private const CLIENT_ID   = '01HZ0000000000000000000005';
    private const TERMINAL_ID = '01HZ0000000000000000000006';
    private const CREATED_BY  = '01HZ0000000000000000000007';
    private const EMPLOYEE_ID = '01HZ000000000000000000000A';
    private const SPARE_ID_1  = '01HZ0000000000000000000008';
    private const SPARE_ID_2  = '01HZ0000000000000000000009';

    public function testReturnsSparePartsByTicketId(): void
    {
        $sparePart1 = TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::SPARE_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            productCode: 'PRT-001',
            serialNumber: null,
            quantity: 2,
            isIncoming: false,
        );

        $sparePartRepo = $this->createMock(TicketSparePartRepositoryInterface::class);
        $sparePartRepo->method('findByTicket')->willReturn([$sparePart1]);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);

        $handler = new ListTicketSparePartsHandler($sparePartRepo, $ticketRepo, $this->createScopeResolver());
        $result = ($handler)(new ListTicketSparePartsQuery(ticketId: self::TICKET_ID));

        self::assertCount(1, $result);
        self::assertSame(self::SPARE_ID_1, (string) $result[0]->id());
        self::assertSame(self::TICKET_ID, (string) $result[0]->ticketId());
        self::assertSame('PRT-001', $result[0]->productCode());
        self::assertSame(2, $result[0]->quantity());
    }

    public function testReturnsScopedPartsByUserId(): void
    {
        $sparePart1 = TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::SPARE_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            productCode: 'PRT-001',
            serialNumber: null,
            quantity: 1,
            isIncoming: false,
        );

        $ticket = Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'Test',
            createdBy: self::CREATED_BY,
        );

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findByEmployeeId')->willReturn([$ticket]);

        $sparePartRepo = $this->createMock(TicketSparePartRepositoryInterface::class);
        $sparePartRepo->method('findByTicket')->willReturn([$sparePart1]);

        $handler = new ListTicketSparePartsHandler($sparePartRepo, $ticketRepo, $this->createScopedResolver(self::EMPLOYEE_ID));
        $result = ($handler)(new ListTicketSparePartsQuery(userId: '01HZ000000000000000000000D'));

        self::assertCount(1, $result);
        self::assertSame(self::SPARE_ID_1, (string) $result[0]->id());
    }

    public function testReturnsSparePartsByEmployeeId(): void
    {
        $ticket1 = Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'Test',
            createdBy: self::CREATED_BY,
        );

        $ticket2 = Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID_2),
            ticketNumber: 'TK-002',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::MANTENIMIENTO,
            description: 'Test 2',
            createdBy: self::CREATED_BY,
        );

        $sparePart1 = TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::SPARE_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            productCode: 'PRT-001',
            serialNumber: null,
            quantity: 1,
            isIncoming: false,
        );

        $sparePart2 = TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::SPARE_ID_2),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID_2),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID_2),
            productCode: 'PRT-002',
            serialNumber: 'SN-001',
            quantity: 1,
            isIncoming: true,
        );

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findByEmployeeId')->willReturn([$ticket1, $ticket2]);

        $sparePartRepo = $this->createMock(TicketSparePartRepositoryInterface::class);
        $sparePartRepo->method('findByTicket')->willReturnCallback(
            fn(TicketIdValueObject $id) => match ((string) $id) {
                self::TICKET_ID => [$sparePart1],
                self::TICKET_ID_2 => [$sparePart2],
                default => [],
            }
        );

        $handler = new ListTicketSparePartsHandler($sparePartRepo, $ticketRepo, $this->createScopeResolver());
        $result = ($handler)(new ListTicketSparePartsQuery(employeeId: self::EMPLOYEE_ID));

        self::assertCount(2, $result);
        self::assertSame(self::SPARE_ID_1, (string) $result[0]->id());
        self::assertSame(self::SPARE_ID_2, (string) $result[1]->id());
    }

    public function testReturnsEmptyWhenNoFilters(): void
    {
        $sparePartRepo = $this->createMock(TicketSparePartRepositoryInterface::class);
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);

        $handler = new ListTicketSparePartsHandler($sparePartRepo, $ticketRepo, $this->createScopeResolver());
        $result = ($handler)(new ListTicketSparePartsQuery());

        self::assertSame([], $result);
    }
}
