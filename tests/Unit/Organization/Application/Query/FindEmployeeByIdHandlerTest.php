<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Query;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Query\FindEmployeeById\FindEmployeeByIdHandler;
use App\Organization\Application\Query\FindEmployeeById\FindEmployeeByIdQuery;
use App\Organization\Application\Query\Response\EmployeeResponse;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindEmployeeByIdHandlerTest extends TestCase
{
    private const EMPLOYEE_ID  = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const VALID_ID_NUM = '1710034065';

    private function makeEmployee(): Employee
    {
        return Employee::hire(
            id:                   new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject(self::VALID_ID_NUM),
            firstName:            'Juan Carlos',
            lastName:             'Pérez García',
            email:                new EmployeeEmailValueObject('juan.perez@example.com'),
            phoneNumber:          '0998765432',
            hiredAt:              new \DateTimeImmutable('2023-01-15'),
            profile:              EmployeeProfile::TECHNICIAN,
        );
    }

    public function testFindsEmployeeByIdSuccessfully(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employee = $this->makeEmployee();
        $employeeRepo->method('findById')->willReturn($employee);

        $handler = new FindEmployeeByIdHandler($employeeRepo);
        $response = ($handler)(new FindEmployeeByIdQuery(employeeId: self::EMPLOYEE_ID));

        self::assertInstanceOf(EmployeeResponse::class, $response);
        self::assertSame(self::EMPLOYEE_ID, $response->id);
        self::assertSame('Juan Carlos', $response->firstName);
        self::assertSame('Pérez García', $response->lastName);
        self::assertSame('juan.perez@example.com', $response->email);
        self::assertSame('technician', $response->profile);
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn(null);

        $handler = new FindEmployeeByIdHandler($employeeRepo);

        $this->expectException(EmployeeNotFoundException::class);
        $this->expectExceptionMessage('Empleado con ID "' . self::EMPLOYEE_ID . '" no encontrado.');

        ($handler)(new FindEmployeeByIdQuery(employeeId: self::EMPLOYEE_ID));
    }
}
