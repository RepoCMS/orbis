<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\CreateVacationRequest\CreateVacationRequestCommand;
use App\Organization\Application\Command\CreateVacationRequest\CreateVacationRequestHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Entities\VacationRequest;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Events\VacationRequested;
use App\Organization\Domain\Exceptions\VacationRequestException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\Repositories\VacationRequestRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreateVacationRequestHandlerTest extends TestCase
{
    private const EMPLOYEE_ID          = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const GENERATED_REQUEST_ID = '01DXZ6NDEKTSV4RRFFQ69G5ABC';
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

    public function testCreatesVacationRequestSuccessfully(): void
    {
        $employeeRepo       = $this->createMock(EmployeeRepositoryInterface::class);
        $vacationRepo       = $this->createMock(VacationRequestRepositoryInterface::class);
        $periodRepo         = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator        = $this->createMock(IdGeneratorInterface::class);
        $publisher          = $this->createMock(DomainEventPublisherInterface::class);

        $employee = $this->makeActiveEmployee();
        $employeeRepo->method('findById')->willReturn($employee);
        $idGenerator->method('generate')->willReturn(self::GENERATED_REQUEST_ID);
        $periodRepo->method('findOverlappingByEmployee')->willReturn([]);
        $vacationRepo->method('findPendingByEmployee')->willReturn([]);

        $savedRequest = null;
        $vacationRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (VacationRequest $r) use (&$savedRequest): void {
                $savedRequest = $r;
            });

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(VacationRequested::class));

        $handler = new CreateVacationRequestHandler(
            $employeeRepo,
            $vacationRepo,
            $periodRepo,
            $idGenerator,
            $publisher,
        );

        $result = ($handler)(new CreateVacationRequestCommand(
            employeeId: self::EMPLOYEE_ID,
            startDate:  '2027-06-01',
            endDate:    '2027-06-10',
        ));

        self::assertSame(self::GENERATED_REQUEST_ID, $result);
        self::assertNotNull($savedRequest);
        self::assertSame(self::EMPLOYEE_ID, (string) $savedRequest->employeeId());
        self::assertTrue($savedRequest->status()->isPending());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo       = $this->createMock(EmployeeRepositoryInterface::class);
        $vacationRepo       = $this->createMock(VacationRequestRepositoryInterface::class);
        $periodRepo         = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator        = $this->createMock(IdGeneratorInterface::class);
        $publisher          = $this->createMock(DomainEventPublisherInterface::class);

        $employeeRepo->method('findById')->willReturn(null);

        $handler = new CreateVacationRequestHandler(
            $employeeRepo,
            $vacationRepo,
            $periodRepo,
            $idGenerator,
            $publisher,
        );

        $this->expectException(EmployeeNotFoundException::class);

        ($handler)(new CreateVacationRequestCommand(
            employeeId: self::EMPLOYEE_ID,
            startDate:  '2027-06-01',
            endDate:    '2027-06-10',
        ));
    }

    public function testThrowsWhenEmployeeInactive(): void
    {
        $employeeRepo       = $this->createMock(EmployeeRepositoryInterface::class);
        $vacationRepo       = $this->createMock(VacationRequestRepositoryInterface::class);
        $periodRepo         = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator        = $this->createMock(IdGeneratorInterface::class);
        $publisher          = $this->createMock(DomainEventPublisherInterface::class);

        $employee = $this->makeActiveEmployee();
        $employee->terminate();
        $employeeRepo->method('findById')->willReturn($employee);

        $handler = new CreateVacationRequestHandler(
            $employeeRepo,
            $vacationRepo,
            $periodRepo,
            $idGenerator,
            $publisher,
        );

        $this->expectException(VacationRequestException::class);
        $this->expectExceptionMessage('No se puede solicitar vacaciones para un empleado inactivo.');

        ($handler)(new CreateVacationRequestCommand(
            employeeId: self::EMPLOYEE_ID,
            startDate:  '2027-06-01',
            endDate:    '2027-06-10',
        ));
    }

    public function testThrowsWhenOverlappingPeriodExists(): void
    {
        $employeeRepo       = $this->createMock(EmployeeRepositoryInterface::class);
        $vacationRepo       = $this->createMock(VacationRequestRepositoryInterface::class);
        $periodRepo         = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator        = $this->createMock(IdGeneratorInterface::class);
        $publisher          = $this->createMock(DomainEventPublisherInterface::class);

        $employee = $this->makeActiveEmployee();
        $employeeRepo->method('findById')->willReturn($employee);
        $idGenerator->method('generate')->willReturn(self::GENERATED_REQUEST_ID);

        $overlapping = [
            \App\Organization\Domain\Entities\EmployeeSpecialPeriod::schedule(
                id:         new \App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject('01DXZ6NDEKTSV4RRFFQ69G5ABC'),
                employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
                type:       \App\Organization\Domain\Enums\EmployeeSpecialPeriodType::VACATION,
                startedAt:  new \DateTimeImmutable('+10 days'),
                endedAt:    new \DateTimeImmutable('+15 days'),
            ),
        ];
        $periodRepo->method('findOverlappingByEmployee')->willReturn($overlapping);

        $handler = new CreateVacationRequestHandler(
            $employeeRepo,
            $vacationRepo,
            $periodRepo,
            $idGenerator,
            $publisher,
        );

        $this->expectException(VacationRequestException::class);
        $this->expectExceptionMessage('El empleado ya tiene un período activo que se solapa con las fechas indicadas.');

        ($handler)(new CreateVacationRequestCommand(
            employeeId: self::EMPLOYEE_ID,
            startDate:  '2027-06-01',
            endDate:    '2027-06-10',
        ));
    }

    public function testThrowsWhenPendingRequestOverlaps(): void
    {
        $employeeRepo       = $this->createMock(EmployeeRepositoryInterface::class);
        $vacationRepo       = $this->createMock(VacationRequestRepositoryInterface::class);
        $periodRepo         = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $idGenerator        = $this->createMock(IdGeneratorInterface::class);
        $publisher          = $this->createMock(DomainEventPublisherInterface::class);

        $employee = $this->makeActiveEmployee();
        $employeeRepo->method('findById')->willReturn($employee);
        $idGenerator->method('generate')->willReturn(self::GENERATED_REQUEST_ID);
        $periodRepo->method('findOverlappingByEmployee')->willReturn([]);

        $pendingRequest = \App\Organization\Domain\Entities\VacationRequest::request(
            id:         new \App\Organization\Domain\ValueObjects\VacationRequestIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAB'),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            startDate:  new \DateTimeImmutable('2027-06-01'),
            endDate:    new \DateTimeImmutable('2027-06-10'),
        );
        $vacationRepo->method('findPendingByEmployee')->willReturn([$pendingRequest]);

        $handler = new CreateVacationRequestHandler(
            $employeeRepo,
            $vacationRepo,
            $periodRepo,
            $idGenerator,
            $publisher,
        );

        $this->expectException(VacationRequestException::class);
        $this->expectExceptionMessage('El empleado ya tiene una solicitud de vacaciones pendiente que se solapa con las fechas indicadas.');

        ($handler)(new CreateVacationRequestCommand(
            employeeId: self::EMPLOYEE_ID,
            startDate:  '2027-06-05',
            endDate:    '2027-06-15',
        ));
    }
}
