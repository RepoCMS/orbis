<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\AssignTechnicianToZone\AssignTechnicianToZoneCommand;
use App\Organization\Application\Command\AssignTechnicianToZone\AssignTechnicianToZoneHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Entities\ServiceZoneAssignment;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\ServiceZoneAssignmentRepositoryInterface;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class AssignTechnicianToZoneHandlerTest extends TestCase
{
    private const TECHNICIAN_ID = '01TECH3NDEKTSV4RRFFQ69G5AA';
    private const ZONE_ID = '01ABCDEFGHJKMNPTQVZ0123456';
    private const ASSIGNMENT_ID = '01ASGN3NDEKTSV4RRFFQ69G5BB';

    private function makeTechnician(): Employee
    {
        return Employee::hire(
            id:                   new EmployeeIdValueObject(self::TECHNICIAN_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1710034065'),
            firstName:            'Carlos',
            lastName:             'Técnico',
            email:                new EmployeeEmailValueObject('carlos.tec@example.com'),
            phoneNumber:          '0998765432',
            hiredAt:              new \DateTimeImmutable('2023-01-15'),
            profile:              EmployeeProfile::TECHNICIAN,
        );
    }

    private function makeZone(): ServiceZone
    {
        return ServiceZone::create(
            new ServiceZoneIdValueObject(self::ZONE_ID),
            'Zona Centro',
        );
    }

    public function testAssignsTechnicianToZoneSuccessfully(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $zoneRepo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $assignmentRepo = $this->createMock(ServiceZoneAssignmentRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $employee = $this->makeTechnician();
        $zone = $this->makeZone();

        $employeeRepo->method('findById')->willReturn($employee);
        $zoneRepo->method('findById')->willReturn($zone);
        $idGenerator->method('generate')->willReturn(self::ASSIGNMENT_ID);

        $savedEmployee = null;
        $employeeRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Employee $e) use (&$savedEmployee): void {
                $savedEmployee = $e;
            });

        $savedAssignment = null;
        $assignmentRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (ServiceZoneAssignment $a) use (&$savedAssignment): void {
                $savedAssignment = $a;
            });

        $handler = new AssignTechnicianToZoneHandler(
            $employeeRepo,
            $zoneRepo,
            $assignmentRepo,
            $idGenerator,
        );

        ($handler)(new AssignTechnicianToZoneCommand(
            technicianId: self::TECHNICIAN_ID,
            serviceZoneId: self::ZONE_ID,
        ));

        self::assertNotNull($savedEmployee);
        self::assertNotNull($savedEmployee->serviceZoneId());
        self::assertSame(self::ZONE_ID, (string) $savedEmployee->serviceZoneId());

        // Verificar que se creó el historial de asignación
        self::assertNotNull($savedAssignment);
        self::assertSame(self::TECHNICIAN_ID, (string) $savedAssignment->employeeId());
        self::assertSame(self::ZONE_ID, (string) $savedAssignment->newZoneId());
        self::assertNull($savedAssignment->previousZoneId());
    }

    public function testCreatesAssignmentHistoryWithPreviousZone(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $zoneRepo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $assignmentRepo = $this->createMock(ServiceZoneAssignmentRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $previousZoneId = new ServiceZoneIdValueObject('01PREV3NDEKTSV4RRFFQ69G5CC');
        $employee = $this->makeTechnician();
        $employee->assignToZone($previousZoneId);

        $newZone = ServiceZone::create(
            new ServiceZoneIdValueObject(self::ZONE_ID),
            'Zona Norte',
        );

        $employeeRepo->method('findById')->willReturn($employee);
        $zoneRepo->method('findById')->willReturn($newZone);
        $idGenerator->method('generate')->willReturn(self::ASSIGNMENT_ID);

        $savedAssignment = null;
        $assignmentRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (ServiceZoneAssignment $a) use (&$savedAssignment): void {
                $savedAssignment = $a;
            });

        $handler = new AssignTechnicianToZoneHandler(
            $employeeRepo,
            $zoneRepo,
            $assignmentRepo,
            $idGenerator,
        );

        ($handler)(new AssignTechnicianToZoneCommand(
            technicianId: self::TECHNICIAN_ID,
            serviceZoneId: self::ZONE_ID,
        ));

        // Verificar que el historial captura la zona anterior
        self::assertNotNull($savedAssignment);
        self::assertNotNull($savedAssignment->previousZoneId());
        self::assertSame((string) $previousZoneId, (string) $savedAssignment->previousZoneId());
        self::assertSame(self::ZONE_ID, (string) $savedAssignment->newZoneId());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $zoneRepo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $assignmentRepo = $this->createMock(ServiceZoneAssignmentRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $employeeRepo->method('findById')->willReturn(null);

        $handler = new AssignTechnicianToZoneHandler(
            $employeeRepo,
            $zoneRepo,
            $assignmentRepo,
            $idGenerator,
        );

        $this->expectException(EmployeeNotFoundException::class);
        $this->expectExceptionMessage('Empleado con ID "' . self::TECHNICIAN_ID . '" no encontrado.');

        ($handler)(new AssignTechnicianToZoneCommand(
            technicianId: self::TECHNICIAN_ID,
            serviceZoneId: self::ZONE_ID,
        ));
    }

    public function testThrowsWhenZoneNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $zoneRepo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $assignmentRepo = $this->createMock(ServiceZoneAssignmentRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $employeeRepo->method('findById')->willReturn($this->makeTechnician());
        $zoneRepo->method('findById')->willReturn(null);

        $handler = new AssignTechnicianToZoneHandler(
            $employeeRepo,
            $zoneRepo,
            $assignmentRepo,
            $idGenerator,
        );

        $this->expectException(ServiceZoneNotFoundException::class);
        $this->expectExceptionMessage('Zona de servicio con ID "' . self::ZONE_ID . '" no encontrada.');

        ($handler)(new AssignTechnicianToZoneCommand(
            technicianId: self::TECHNICIAN_ID,
            serviceZoneId: self::ZONE_ID,
        ));
    }
}
