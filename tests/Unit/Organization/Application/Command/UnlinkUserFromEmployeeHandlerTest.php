<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\UnlinkUserFromEmployee\UnlinkUserFromEmployeeCommand;
use App\Organization\Application\Command\UnlinkUserFromEmployee\UnlinkUserFromEmployeeHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Events\EmployeeUserUnlinked;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class UnlinkUserFromEmployeeHandlerTest extends TestCase
{
    private const EMPLOYEE_ID  = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const USER_ID      = '01JCPX8XYZ0000000000000001';
    private const VALID_ID_NUM = '1710034065';

    private function makeEmployeeWithUser(): Employee
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
            userId:               new UserIdValueObject(self::USER_ID),
        );
    }

    private function makeEmployeeWithoutUser(): Employee
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

    public function testUnlinksUserSuccessfully(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $userRepo = $this->createMock(UserRepositoryInterface::class);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $savedEmployee = null;
        $employeeRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Employee $e) use (&$savedEmployee): void {
                $savedEmployee = $e;
            });

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(EmployeeUserUnlinked::class));

        $handler = new UnlinkUserFromEmployeeHandler($employeeRepo, $userRepo, $publisher);
        ($handler)(new UnlinkUserFromEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertNotNull($savedEmployee);
        self::assertNull($savedEmployee->userId());
        self::assertFalse($savedEmployee->hasUser());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn(null);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new UnlinkUserFromEmployeeHandler($employeeRepo, $userRepo, $publisher);

        $this->expectException(EmployeeNotFoundException::class);

        ($handler)(new UnlinkUserFromEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
        ));
    }

    public function testDoesNothingWhenEmployeeHasNoUser(): void
    {
        $employee = $this->makeEmployeeWithoutUser();
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $employeeRepo->expects($this->never())->method('save');
        $publisher->expects($this->never())->method('publish');

        $handler = new UnlinkUserFromEmployeeHandler($employeeRepo, $userRepo, $publisher);
        ($handler)(new UnlinkUserFromEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertNull($employee->userId());
    }

    public function testPublishesEventWithCorrectIds(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (EmployeeUserUnlinked $event) {
                return $event->employeeId === self::EMPLOYEE_ID
                    && $event->userId === self::USER_ID;
            }));

        $handler = new UnlinkUserFromEmployeeHandler($employeeRepo, $userRepo, $publisher);
        ($handler)(new UnlinkUserFromEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
        ));
    }
}
