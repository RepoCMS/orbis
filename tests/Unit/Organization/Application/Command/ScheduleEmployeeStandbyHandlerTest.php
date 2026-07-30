<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\ScheduleEmployeeStandby\ScheduleEmployeeStandbyCommand;
use App\Organization\Application\Command\ScheduleEmployeeStandby\ScheduleEmployeeStandbyHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Entities\EmployeeSpecialPeriod;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodStatus;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class ScheduleEmployeeStandbyHandlerTest extends TestCase
{
    private const EMPLOYEE_ID         = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const GENERATED_PERIOD_ID = '01DXZ6NDEKTSV4RRFFQ69G5ABC';
    private const VALID_ID            = '1710034065';

    private function makeActiveEmployee(): Employee
    {
        return Employee::hire(
            id:                   new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject(self::VALID_ID),
            firstName:            'Juan Carlos',
            lastName:             'Pérez García',
            email:                new EmployeeEmailValueObject('juan.perez@example.com'),
            phoneNumber:          '0998765432',
            hiredAt:              new \DateTimeImmutable('2023-01-01'),
            profile:              EmployeeProfile::TECHNICIAN,
        );
    }

    public function testSchedulesStandbySuccessfully(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $periodRepo   = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);

        $employee = $this->makeActiveEmployee();
        $employeeRepo->method('findById')->willReturn($employee);
        $idGenerator->method('generate')->willReturn(self::GENERATED_PERIOD_ID);
        $periodRepo->method('findOverlappingByEmployee')->willReturn([]);

        $savedPeriod = null;
        $periodRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (EmployeeSpecialPeriod $p) use (&$savedPeriod): void {
                $savedPeriod = $p;
            });

        $handler = new ScheduleEmployeeStandbyHandler($employeeRepo, $periodRepo, $idGenerator);

        ($handler)(new ScheduleEmployeeStandbyCommand(
            employeeId: self::EMPLOYEE_ID,
            startedAt:  '2027-07-01',
            endedAt:    '2027-07-02',
            notes:      'Standby por mantenimiento de fin de semana',
        ));

        self::assertNotNull($savedPeriod);
        self::assertSame(self::GENERATED_PERIOD_ID, (string) $savedPeriod->id());
        self::assertSame(self::EMPLOYEE_ID, (string) $savedPeriod->employeeId());
        self::assertSame('standby', $savedPeriod->type()->value);
        self::assertSame(EmployeeSpecialPeriodStatus::SCHEDULED, $savedPeriod->status());
        self::assertSame('Standby por mantenimiento de fin de semana', $savedPeriod->notes());
    }

    public function testSchedulesStandbyWithoutNotes(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $periodRepo   = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);

        $employeeRepo->method('findById')->willReturn($this->makeActiveEmployee());
        $idGenerator->method('generate')->willReturn(self::GENERATED_PERIOD_ID);
        $periodRepo->method('findOverlappingByEmployee')->willReturn([]);

        $savedPeriod = null;
        $periodRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (EmployeeSpecialPeriod $p) use (&$savedPeriod): void {
                $savedPeriod = $p;
            });

        $handler = new ScheduleEmployeeStandbyHandler($employeeRepo, $periodRepo, $idGenerator);

        ($handler)(new ScheduleEmployeeStandbyCommand(
            employeeId: self::EMPLOYEE_ID,
            startedAt:  '2027-07-01',
            endedAt:    '2027-07-02',
        ));

        self::assertNotNull($savedPeriod);
        self::assertNull($savedPeriod->notes());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $periodRepo   = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);

        $employeeRepo->method('findById')->willReturn(null);

        $handler = new ScheduleEmployeeStandbyHandler($employeeRepo, $periodRepo, $idGenerator);

        $this->expectException(EmployeeNotFoundException::class);

        ($handler)(new ScheduleEmployeeStandbyCommand(
            employeeId: self::EMPLOYEE_ID,
            startedAt:  '2027-07-01',
            endedAt:    '2027-07-02',
        ));
    }

    public function testThrowsWhenEmployeeInactive(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $periodRepo   = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);

        $employee = $this->makeActiveEmployee();
        $employee->terminate();
        $employeeRepo->method('findById')->willReturn($employee);

        $handler = new ScheduleEmployeeStandbyHandler($employeeRepo, $periodRepo, $idGenerator);

        $this->expectException(EmployeeSpecialPeriodException::class);
        $this->expectExceptionMessage('No se puede programar un standby para un empleado inactivo.');

        ($handler)(new ScheduleEmployeeStandbyCommand(
            employeeId: self::EMPLOYEE_ID,
            startedAt:  '2027-07-01',
            endedAt:    '2027-07-02',
        ));
    }

    public function testThrowsWhenOverlappingPeriodExists(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $periodRepo   = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);

        $employeeRepo->method('findById')->willReturn($this->makeActiveEmployee());
        $idGenerator->method('generate')->willReturn(self::GENERATED_PERIOD_ID);
        // EmployeeSpecialPeriod es final — crear instancias reales para evitar mock de clase final
        $overlapping = [
            \App\Organization\Domain\Entities\EmployeeSpecialPeriod::schedule(
                id:         new \App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject('01DXZ6NDEKTSV4RRFFQ69G5ABC'),
                employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
                type:       \App\Organization\Domain\Enums\EmployeeSpecialPeriodType::STANDBY,
                startedAt:  new \DateTimeImmutable('+10 days'),
                endedAt:    new \DateTimeImmutable('+15 days'),
            ),
        ];
        $periodRepo->method('findOverlappingByEmployee')->willReturn($overlapping);

        $handler = new ScheduleEmployeeStandbyHandler($employeeRepo, $periodRepo, $idGenerator);

        $this->expectException(EmployeeSpecialPeriodException::class);
        $this->expectExceptionMessage('El empleado ya tiene un período activo que se solapa con las fechas indicadas.');

        ($handler)(new ScheduleEmployeeStandbyCommand(
            employeeId: self::EMPLOYEE_ID,
            startedAt:  '2027-07-01',
            endedAt:    '2027-07-02',
        ));
    }

    public function testStandbyDoesNotChangeEmployeeStatus(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $periodRepo   = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);

        $employee = $this->makeActiveEmployee();
        $employeeRepo->method('findById')->willReturn($employee);
        $idGenerator->method('generate')->willReturn(self::GENERATED_PERIOD_ID);
        $periodRepo->method('findOverlappingByEmployee')->willReturn([]);

        // Employee repo save NO debe ser llamado (standby no cambia estado)
        $employeeRepo->expects($this->never())->method('save');

        $handler = new ScheduleEmployeeStandbyHandler($employeeRepo, $periodRepo, $idGenerator);

        ($handler)(new ScheduleEmployeeStandbyCommand(
            employeeId: self::EMPLOYEE_ID,
            startedAt:  '2027-07-01',
            endedAt:    '2027-07-02',
        ));
    }
}
