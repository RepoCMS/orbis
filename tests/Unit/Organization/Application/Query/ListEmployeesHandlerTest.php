<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Query;

use App\Organization\Application\Query\ListEmployees\ListEmployeesHandler;
use App\Organization\Application\Query\ListEmployees\ListEmployeesQuery;
use App\Organization\Application\Query\ListEmployees\ListEmployeesResult;
use App\Organization\Application\Query\Response\EmployeeResponse;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListEmployeesHandlerTest extends TestCase
{
    public function testReturnsAllEmployeesWithPagination(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employee1 = Employee::hire(
            id:                   new EmployeeIdValueObject('01JARZ3NDEKTSV4RRFFQ69G5FV'),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1710034065'),
            firstName:            'Juan Carlos',
            lastName:             'Pérez García',
            email:                new EmployeeEmailValueObject('juan.perez@example.com'),
            phoneNumber:          '0998765432',
            hiredAt:              new \DateTimeImmutable('2023-01-15'),
            profile:              EmployeeProfile::TECHNICIAN,
        );

        $employee2 = Employee::hire(
            id:                   new EmployeeIdValueObject('01JARZ3NDEKTSV4RRFFQ69G5FX'),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1723456784'),
            firstName:            'María Elena',
            lastName:             'García López',
            email:                new EmployeeEmailValueObject('maria.garcia@example.com'),
            phoneNumber:          '0987654321',
            hiredAt:              new \DateTimeImmutable('2023-06-01'),
            profile:              EmployeeProfile::COORDINATOR,
        );

        $employeeRepo->method('findByFilters')
            ->with(null, null, null, null, 1, null)
            ->willReturn([$employee1, $employee2]);
        $employeeRepo->method('countByFilters')
            ->with(null, null, null, null)
            ->willReturn(2);

        $handler = new ListEmployeesHandler($employeeRepo);

        $result = ($handler)(new ListEmployeesQuery());

        self::assertInstanceOf(ListEmployeesResult::class, $result);
        self::assertCount(2, $result->items);
        self::assertInstanceOf(EmployeeResponse::class, $result->items[0]);
        self::assertInstanceOf(EmployeeResponse::class, $result->items[1]);
        self::assertSame('Juan Carlos Pérez García', $result->items[0]->fullName);
        self::assertSame('María Elena García López', $result->items[1]->fullName);
        self::assertSame('technician', $result->items[0]->profile);
        self::assertSame('coordinator', $result->items[1]->profile);
        self::assertSame(2, $result->total);
        self::assertSame(1, $result->page);
        self::assertNull($result->limit);
    }

    public function testHandlesEmptyResultSet(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByFilters')->willReturn([]);
        $employeeRepo->method('countByFilters')->willReturn(0);

        $handler = new ListEmployeesHandler($employeeRepo);

        $result = ($handler)(new ListEmployeesQuery());

        self::assertInstanceOf(ListEmployeesResult::class, $result);
        self::assertCount(0, $result->items);
        self::assertSame(0, $result->total);
    }

    public function testReturnsCorrectResponseTypes(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employee = Employee::hire(
            id:                   new EmployeeIdValueObject('01JARZ3NDEKTSV4RRFFQ69G5FV'),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1710034065'),
            firstName:            'Juan Carlos',
            lastName:             'Pérez García',
            email:                new EmployeeEmailValueObject('juan.perez@example.com'),
            phoneNumber:          '0998765432',
            hiredAt:              new \DateTimeImmutable('2023-01-15'),
            profile:              EmployeeProfile::ADMINISTRATIVE,
        );

        $employeeRepo->method('findByFilters')->willReturn([$employee]);
        $employeeRepo->method('countByFilters')->willReturn(1);

        $handler = new ListEmployeesHandler($employeeRepo);

        $result = ($handler)(new ListEmployeesQuery());

        self::assertCount(1, $result->items);
        self::assertInstanceOf(EmployeeResponse::class, $result->items[0]);
        self::assertIsString($result->items[0]->id);
        self::assertIsString($result->items[0]->firstName);
        self::assertIsString($result->items[0]->lastName);
        self::assertIsString($result->items[0]->email);
        self::assertIsString($result->items[0]->status);
        self::assertIsString($result->items[0]->profile);
        self::assertSame('active', $result->items[0]->status);
    }

    public function testUsesFindByFiltersWhenProfileProvided(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employeeRepo->expects($this->once())
            ->method('findByFilters')
            ->with(EmployeeProfile::TECHNICIAN, null, null, null, 1, null)
            ->willReturn([]);
        $employeeRepo->method('countByFilters')
            ->with(EmployeeProfile::TECHNICIAN, null, null, null)
            ->willReturn(0);

        $handler = new ListEmployeesHandler($employeeRepo);
        ($handler)(new ListEmployeesQuery(profile: EmployeeProfile::TECHNICIAN));

        self::assertTrue(true);
    }

    public function testUsesFindByFiltersWhenStatusProvided(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employeeRepo->expects($this->once())
            ->method('findByFilters')
            ->with(null, EmployeeStatus::ACTIVE, null, null, 1, null)
            ->willReturn([]);
        $employeeRepo->method('countByFilters')
            ->with(null, EmployeeStatus::ACTIVE, null, null)
            ->willReturn(0);

        $handler = new ListEmployeesHandler($employeeRepo);
        ($handler)(new ListEmployeesQuery(status: EmployeeStatus::ACTIVE));

        self::assertTrue(true);
    }

    public function testUsesFindByFiltersWhenSearchProvided(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employeeRepo->expects($this->once())
            ->method('findByFilters')
            ->with(null, null, 'Juan', null, 1, null)
            ->willReturn([]);
        $employeeRepo->method('countByFilters')
            ->with(null, null, 'Juan', null)
            ->willReturn(0);

        $handler = new ListEmployeesHandler($employeeRepo);
        ($handler)(new ListEmployeesQuery(search: 'Juan'));

        self::assertTrue(true);
    }

    public function testPassesServiceZoneIdAndPagination(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employeeRepo->expects($this->once())
            ->method('findByFilters')
            ->with(null, null, null, '01ZONE1234567890', 3, 50)
            ->willReturn([]);
        $employeeRepo->expects($this->once())
            ->method('countByFilters')
            ->with(null, null, null, '01ZONE1234567890')
            ->willReturn(0);

        $handler = new ListEmployeesHandler($employeeRepo);
        $result = ($handler)(new ListEmployeesQuery(
            serviceZoneId: '01ZONE1234567890',
            page:          3,
            limit:         50,
        ));

        self::assertSame(3, $result->page);
        self::assertSame(50, $result->limit);
        self::assertSame(0, $result->total);
    }

    public function testUsesFindByFiltersWhenNoFiltersProvided(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employeeRepo->expects($this->once())
            ->method('findByFilters')
            ->with(null, null, null, null, 1, null)
            ->willReturn([]);
        $employeeRepo->method('countByFilters')
            ->with(null, null, null, null)
            ->willReturn(0);
        $employeeRepo->expects($this->never())->method('findAll');

        $handler = new ListEmployeesHandler($employeeRepo);
        ($handler)(new ListEmployeesQuery());

        self::assertTrue(true);
    }
}
