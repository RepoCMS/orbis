<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Organization\Application\Command\DeactivateEmployee\DeactivateEmployeeCommand;
use App\Organization\Application\Command\DeactivateEmployee\DeactivateEmployeeHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Exception\TerminationBlockedException;
use App\Organization\Application\Service\TerminationVerificationServiceInterface;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Events\EmployeeDeactivated;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmploymentPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class DeactivateEmployeeHandlerTest extends TestCase
{
    private const EMPLOYEE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    private function makeExistingEmployee(): Employee
    {
        return Employee::hire(
            id:                   new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1710034065'),
            firstName:            'Juan Carlos',
            lastName:             'Pérez García',
            email:                new EmployeeEmailValueObject('juan.perez@example.com'),
            phoneNumber:          '0998765432',
            hiredAt:              new \DateTimeImmutable('2023-01-01'),
            profile:              EmployeeProfile::TECHNICIAN,
        );
    }

    public function testDeactivatesEmployeeSuccessfully(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $periodRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $terminationVerification = $this->createMock(TerminationVerificationServiceInterface::class);

        $employee = $this->makeExistingEmployee();
        $employeeRepo->method('findById')->willReturn($employee);

        $terminationVerification->method('findBlockingItems')->willReturn([]);

        $savedEmployee = null;
        $employeeRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Employee $e) use (&$savedEmployee): void {
                $savedEmployee = $e;
            });

        $periodRepo->method('findActiveByEmployee')->willReturn(null);

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(EmployeeDeactivated::class));

        $handler = new DeactivateEmployeeHandler(
            $employeeRepo,
            $periodRepo,
            $userRepo,
            $publisher,
            $terminationVerification,
        );

        ($handler)(new DeactivateEmployeeCommand(employeeId: self::EMPLOYEE_ID));

        self::assertNotNull($savedEmployee);
        self::assertSame(EmployeeStatus::INACTIVE, $savedEmployee->status());
        self::assertNotNull($savedEmployee->terminatedAt());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $periodRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $terminationVerification = $this->createMock(TerminationVerificationServiceInterface::class);

        $employeeRepo->method('findById')->willReturn(null);

        $handler = new DeactivateEmployeeHandler(
            $employeeRepo,
            $periodRepo,
            $userRepo,
            $publisher,
            $terminationVerification,
        );

        $this->expectException(EmployeeNotFoundException::class);
        $this->expectExceptionMessage('Empleado con ID "' . self::EMPLOYEE_ID . '" no encontrado.');

        ($handler)(new DeactivateEmployeeCommand(employeeId: self::EMPLOYEE_ID));
    }

    public function testThrowsWhenPendingItemsExist(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $periodRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $terminationVerification = $this->createMock(TerminationVerificationServiceInterface::class);

        $employee = $this->makeExistingEmployee();
        $employeeRepo->method('findById')->willReturn($employee);

        $pendingItems = [
            'support' => [
                ['type' => 'ticket', 'description' => 'Ticket #001 — Asignado'],
            ],
            'organization' => [
                ['type' => 'special_period', 'description' => 'Vacaciones (2024-01-01 — 2024-01-15)'],
            ],
        ];
        $terminationVerification->method('findBlockingItems')->willReturn($pendingItems);

        $handler = new DeactivateEmployeeHandler(
            $employeeRepo,
            $periodRepo,
            $userRepo,
            $publisher,
            $terminationVerification,
        );

        $this->expectException(TerminationBlockedException::class);

        ($handler)(new DeactivateEmployeeCommand(employeeId: self::EMPLOYEE_ID));
    }
}
