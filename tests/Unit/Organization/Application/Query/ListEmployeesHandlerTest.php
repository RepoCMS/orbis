<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Query;

use App\Organization\Application\Query\ListEmployees\ListEmployeesHandler;
use App\Organization\Application\Query\ListEmployees\ListEmployeesQuery;
use App\Organization\Application\Query\Response\EmployeeResponse;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
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

        $employeeRepo->method('findAll')->willReturn([$employee1, $employee2]);

        $handler = new ListEmployeesHandler($employeeRepo);

        $result = ($handler)(new ListEmployeesQuery());

        self::assertCount(2, $result);
        self::assertInstanceOf(EmployeeResponse::class, $result[0]);
        self::assertInstanceOf(EmployeeResponse::class, $result[1]);
        self::assertSame('Juan Carlos Pérez García', $result[0]->fullName);
        self::assertSame('María Elena García López', $result[1]->fullName);
        self::assertSame('technician', $result[0]->profile);
        self::assertSame('coordinator', $result[1]->profile);
    }

    public function testHandlesEmptyResultSet(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findAll')->willReturn([]);

        $handler = new ListEmployeesHandler($employeeRepo);

        $result = ($handler)(new ListEmployeesQuery());

        self::assertIsArray($result);
        self::assertCount(0, $result);
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

        $employeeRepo->method('findAll')->willReturn([$employee]);

        $handler = new ListEmployeesHandler($employeeRepo);

        $result = ($handler)(new ListEmployeesQuery());

        self::assertCount(1, $result);
        self::assertInstanceOf(EmployeeResponse::class, $result[0]);
        self::assertIsString($result[0]->id);
        self::assertIsString($result[0]->firstName);
        self::assertIsString($result[0]->lastName);
        self::assertIsString($result[0]->email);
        self::assertIsString($result[0]->status);
        self::assertIsString($result[0]->profile);
        self::assertSame('active', $result[0]->status);
    }

    public function testUsesFindByFiltersWhenProfileProvided(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employeeRepo->expects($this->once())
            ->method('findByFilters')
            ->with(EmployeeProfile::TECHNICIAN, null, null)
            ->willReturn([]);

        $employeeRepo->expects($this->never())->method('findAll');

        $handler = new ListEmployeesHandler($employeeRepo);
        ($handler)(new ListEmployeesQuery(profile: EmployeeProfile::TECHNICIAN));

        self::assertTrue(true);
    }

    public function testUsesFindByFiltersWhenStatusProvided(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employeeRepo->expects($this->once())
            ->method('findByFilters')
            ->with(null, EmployeeStatus::ACTIVE, null)
            ->willReturn([]);

        $handler = new ListEmployeesHandler($employeeRepo);
        ($handler)(new ListEmployeesQuery(status: EmployeeStatus::ACTIVE));

        self::assertTrue(true);
    }

    public function testUsesFindByFiltersWhenSearchProvided(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employeeRepo->expects($this->once())
            ->method('findByFilters')
            ->with(null, null, 'Juan')
            ->willReturn([]);

        $handler = new ListEmployeesHandler($employeeRepo);
        ($handler)(new ListEmployeesQuery(search: 'Juan'));

        self::assertTrue(true);
    }

    public function testUsesFindAllWhenNoFiltersProvided(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employeeRepo->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $employeeRepo->expects($this->never())->method('findByFilters');

        $handler = new ListEmployeesHandler($employeeRepo);
        ($handler)(new ListEmployeesQuery());

        self::assertTrue(true);
    }
}
