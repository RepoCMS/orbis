<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Application\Command\ReactivateEmployee\ReactivateEmployeeCommand;
use App\Organization\Application\Command\ReactivateEmployee\ReactivateEmployeeHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Entities\EmploymentPeriod;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Events\EmployeeReactivated;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmploymentPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class ReactivateEmployeeHandlerTest extends TestCase
{
    private const EMPLOYEE_ID  = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const PERIOD_ID    = '01PR0PR0PR0PR0PR0PR0PR0PR0';
    private const VALID_ID_NUM = '1710034065';

    private function makeInactiveEmployee(): Employee
    {
        $employee = Employee::hire(
            id:                   new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject(self::VALID_ID_NUM),
            firstName:            'Juan Carlos',
            lastName:             'Pérez García',
            email:                new EmployeeEmailValueObject('juan.perez@example.com'),
            phoneNumber:          '0998765432',
            hiredAt:              new \DateTimeImmutable('2023-01-15'),
            profile:              EmployeeProfile::TECHNICIAN,
        );
        $employee->terminate();
        $employee->linkUser(new UserIdValueObject('01JCPX8XYZ0000000000000001'));

        return $employee;
    }

    public function testReactivatesEmployeeSuccessfully(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employmentPeriodRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $employee = $this->makeInactiveEmployee();
        $employeeRepo->method('findById')->willReturn($employee);
        $idGenerator->method('generate')->willReturn(self::PERIOD_ID);

        $savedEmployee = null;
        $employeeRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Employee $e) use (&$savedEmployee): void {
                $savedEmployee = $e;
            });

        $employmentPeriodRepo->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(EmploymentPeriod::class));

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(EmployeeReactivated::class));

        $handler = new ReactivateEmployeeHandler($employeeRepo, $employmentPeriodRepo, $idGenerator, $publisher);
        ($handler)(new ReactivateEmployeeCommand(employeeId: self::EMPLOYEE_ID));

        self::assertNotNull($savedEmployee);
        self::assertSame(EmployeeStatus::ACTIVE, $savedEmployee->status());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employmentPeriodRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $employeeRepo->method('findById')->willReturn(null);

        $handler = new ReactivateEmployeeHandler($employeeRepo, $employmentPeriodRepo, $idGenerator, $publisher);

        $this->expectException(EmployeeNotFoundException::class);
        $this->expectExceptionMessage('Empleado con ID "' . self::EMPLOYEE_ID . '" no encontrado.');

        ($handler)(new ReactivateEmployeeCommand(employeeId: self::EMPLOYEE_ID));
    }
}
