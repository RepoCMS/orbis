<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\UpdateEmployee\UpdateEmployeeCommand;
use App\Organization\Application\Command\UpdateEmployee\UpdateEmployeeHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateEmployeeHandlerTest extends TestCase
{
    private const EMPLOYEE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testUpdatesEmployee(): void
    {
        $employee = Employee::hire(
            id: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1710034065'),
            firstName: 'Juan Carlos',
            lastName: 'Pérez García',
            email: new EmployeeEmailValueObject('juan@example.com'),
            phoneNumber: '0998765432',
            profile: EmployeeProfile::TECHNICIAN,
            hiredAt: new \DateTimeImmutable('2024-01-15'),
        );

        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->method('findById')->willReturn($employee);
        $repo->expects($this->once())->method('save');

        $handler = new UpdateEmployeeHandler($repo);
        ($handler)(new UpdateEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
            firstName: 'Juan Updated',
            lastName: 'Pérez Updated',
            email: 'juan.updated@example.com',
            phoneNumber: '0998765433',
            profile: 'technician',
        ));

        self::assertSame('Juan Updated', $employee->firstName());
        self::assertSame('Pérez Updated', $employee->lastName());
        self::assertSame('juan.updated@example.com', (string) $employee->email());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateEmployeeHandler($repo);

        $this->expectException(EmployeeNotFoundException::class);

        ($handler)(new UpdateEmployeeCommand(
            employeeId: '01HZ0000000000000000000099',
            firstName: 'Test',
            lastName: 'Test User',
            email: 'test@example.com',
            phoneNumber: '0998765432',
            profile: 'technician',
        ));
    }
}
