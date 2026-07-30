<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Organization\Application\Command\SoftDeleteEmployee\SoftDeleteEmployeeCommand;
use App\Organization\Application\Command\SoftDeleteEmployee\SoftDeleteEmployeeHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Events\EmployeeDeleted;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class SoftDeleteEmployeeHandlerTest extends TestCase
{
    private const EMPLOYEE_ID  = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const VALID_ID_NUM = '1710034065';

    private function makeActiveEmployee(): Employee
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

    public function testSoftDeletesEmployeeSuccessfully(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $employee = $this->makeActiveEmployee();
        $employeeRepo->method('findById')->willReturn($employee);

        $savedEmployee = null;
        $employeeRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Employee $e) use (&$savedEmployee): void {
                $savedEmployee = $e;
            });

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(EmployeeDeleted::class));

        $handler = new SoftDeleteEmployeeHandler($employeeRepo, $userRepo, $publisher);
        ($handler)(new SoftDeleteEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertNotNull($savedEmployee);
        self::assertTrue($savedEmployee->isDeleted());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $employeeRepo->method('findById')->willReturn(null);

        $handler = new SoftDeleteEmployeeHandler($employeeRepo, $userRepo, $publisher);

        $this->expectException(EmployeeNotFoundException::class);
        $this->expectExceptionMessage('Empleado con ID "' . self::EMPLOYEE_ID . '" no encontrado.');

        ($handler)(new SoftDeleteEmployeeCommand(employeeId: self::EMPLOYEE_ID));
    }

    public function testPublishesEventWithCorrectEmployeeId(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $employee = $this->makeActiveEmployee();
        $employeeRepo->method('findById')->willReturn($employee);

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (EmployeeDeleted $event) {
                return $event->employeeId === self::EMPLOYEE_ID;
            }));

        $handler = new SoftDeleteEmployeeHandler($employeeRepo, $userRepo, $publisher);
        ($handler)(new SoftDeleteEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
        ));
    }
}
