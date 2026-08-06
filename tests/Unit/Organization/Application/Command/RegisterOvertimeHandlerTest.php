<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\RegisterOvertime\RegisterOvertimeCommand;
use App\Organization\Application\Command\RegisterOvertime\RegisterOvertimeHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Entities\OvertimeRecord;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Events\OvertimeRegistered;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RegisterOvertimeHandlerTest extends TestCase
{
    private const EMPLOYEE_ID          = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const GENERATED_RECORD_ID  = '01DXZ6NDEKTSV4RRFFQ69G5ABC';
    private const VALID_ID             = '1710034065';

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

    public function testRegistersOvertimeSuccessfully(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $overtimeRepo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);
        $publisher    = $this->createMock(DomainEventPublisherInterface::class);

        $employeeRepo->method('findById')->willReturn($this->makeActiveEmployee());
        $idGenerator->method('generate')->willReturn(self::GENERATED_RECORD_ID);

        $savedRecord = null;
        $overtimeRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (OvertimeRecord $r) use (&$savedRecord): void {
                $savedRecord = $r;
            });

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(OvertimeRegistered::class));

        $handler = new RegisterOvertimeHandler($employeeRepo, $overtimeRepo, $idGenerator, $publisher);

        $result = ($handler)(new RegisterOvertimeCommand(
            employeeId: self::EMPLOYEE_ID,
            workedAt:   '2026-05-24',
            startTime:  '2026-05-24 18:00',
            endTime:    '2026-05-24 20:30',
            reason:     'Reparación urgente de cajero',
        ));

        self::assertSame(self::GENERATED_RECORD_ID, $result);
        self::assertNotNull($savedRecord);
        self::assertSame(self::EMPLOYEE_ID, (string) $savedRecord->employeeId());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $overtimeRepo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);
        $publisher    = $this->createMock(DomainEventPublisherInterface::class);

        $employeeRepo->method('findById')->willReturn(null);

        $handler = new RegisterOvertimeHandler($employeeRepo, $overtimeRepo, $idGenerator, $publisher);

        $this->expectException(EmployeeNotFoundException::class);

        ($handler)(new RegisterOvertimeCommand(
            employeeId: self::EMPLOYEE_ID,
            workedAt:   '2026-05-24',
            startTime:  '2026-05-24 18:00',
            endTime:    '2026-05-24 20:30',
            reason:     'Reparación urgente de cajero',
        ));
    }

    public function testThrowsWhenEmployeeInactive(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $overtimeRepo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);
        $publisher    = $this->createMock(DomainEventPublisherInterface::class);

        $employee = $this->makeActiveEmployee();
        $employee->terminate();
        $employeeRepo->method('findById')->willReturn($employee);

        $handler = new RegisterOvertimeHandler($employeeRepo, $overtimeRepo, $idGenerator, $publisher);

        $this->expectException(EmployeeNotFoundException::class);

        ($handler)(new RegisterOvertimeCommand(
            employeeId: self::EMPLOYEE_ID,
            workedAt:   '2026-05-24',
            startTime:  '2026-05-24 18:00',
            endTime:    '2026-05-24 20:30',
            reason:     'Reparación urgente de cajero',
        ));
    }
}
