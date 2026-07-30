<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\CancelEmployeeSpecialPeriod\CancelEmployeeSpecialPeriodCommand;
use App\Organization\Application\Command\CancelEmployeeSpecialPeriod\CancelEmployeeSpecialPeriodHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Entities\EmployeeSpecialPeriod;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodStatus;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use PHPUnit\Framework\TestCase;

final class CancelEmployeeSpecialPeriodHandlerTest extends TestCase
{
    private const PERIOD_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const EMPLOYEE_ID = '01HZ0000000000000000000001';

    public function testCancelsScheduledPeriod(): void
    {
        $period = EmployeeSpecialPeriod::schedule(
            id: EmployeeSpecialPeriodIdValueObject::fromString(self::PERIOD_ID),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            type: EmployeeSpecialPeriodType::STANDBY,
            startedAt: new \DateTimeImmutable('+10 days'),
            endedAt: new \DateTimeImmutable('+11 days'),
        );

        $periodRepo = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $periodRepo->method('findById')->willReturn($period);
        $periodRepo->expects($this->once())->method('save');

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->expects($this->never())->method('save');

        $handler = new CancelEmployeeSpecialPeriodHandler($employeeRepo, $periodRepo);
        ($handler)(new CancelEmployeeSpecialPeriodCommand(
            periodId: self::PERIOD_ID,
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertSame(EmployeeSpecialPeriodStatus::CANCELLED, $period->status());
    }

    public function testCancelsInProgressVacationAndReturnsEmployee(): void
    {
        $period = EmployeeSpecialPeriod::schedule(
            id: EmployeeSpecialPeriodIdValueObject::fromString(self::PERIOD_ID),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            type: EmployeeSpecialPeriodType::VACATION,
            startedAt: new \DateTimeImmutable('-10 days'),
            endedAt: new \DateTimeImmutable('+1 days'),
        );

        $employee = Employee::hire(
            id: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1710034065'),
            firstName: 'Test Employee',
            lastName: 'Apellido Test',
            email: new EmployeeEmailValueObject('employee@example.com'),
            phoneNumber: '0998765432',
            profile: EmployeeProfile::TECHNICIAN,
            hiredAt: new \DateTimeImmutable('2023-01-01'),
        );
        $employee->startVacation();
        self::assertSame(\App\Organization\Domain\Enums\EmployeeStatus::ON_VACATION, $employee->status());

        $periodRepo = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $periodRepo->method('findById')->willReturn($period);
        $periodRepo->expects($this->once())->method('save');

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);
        $employeeRepo->expects($this->once())->method('save');

        $handler = new CancelEmployeeSpecialPeriodHandler($employeeRepo, $periodRepo);
        ($handler)(new CancelEmployeeSpecialPeriodCommand(
            periodId: self::PERIOD_ID,
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertSame(EmployeeSpecialPeriodStatus::CANCELLED, $period->status());
        self::assertTrue($employee->isActive());
    }

    public function testThrowsWhenPeriodNotFound(): void
    {
        $periodRepo = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $periodRepo->method('findById')->willReturn(null);

        $handler = new CancelEmployeeSpecialPeriodHandler(
            $this->createMock(EmployeeRepositoryInterface::class),
            $periodRepo,
        );

        $this->expectException(EmployeeSpecialPeriodException::class);

        ($handler)(new CancelEmployeeSpecialPeriodCommand(
            periodId: '01HZ0000000000000000000099',
            employeeId: self::EMPLOYEE_ID,
        ));
    }

    public function testThrowsWhenEmployeeNotFoundDuringVacationCancel(): void
    {
        $period = EmployeeSpecialPeriod::schedule(
            id: EmployeeSpecialPeriodIdValueObject::fromString(self::PERIOD_ID),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            type: EmployeeSpecialPeriodType::VACATION,
            startedAt: new \DateTimeImmutable('-10 days'),
            endedAt: new \DateTimeImmutable('+1 days'),
        );

        $periodRepo = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $periodRepo->method('findById')->willReturn($period);

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn(null);

        $handler = new CancelEmployeeSpecialPeriodHandler($employeeRepo, $periodRepo);

        $this->expectException(EmployeeNotFoundException::class);

        ($handler)(new CancelEmployeeSpecialPeriodCommand(
            periodId: self::PERIOD_ID,
            employeeId: self::EMPLOYEE_ID,
        ));
    }
}
