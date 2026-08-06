<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\RemoveTechnicianFromZone\RemoveTechnicianFromZoneCommand;
use App\Organization\Application\Command\RemoveTechnicianFromZone\RemoveTechnicianFromZoneHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class RemoveTechnicianFromZoneHandlerTest extends TestCase
{
    private const TECHNICIAN_ID = '01TECH3NDEKTSV4RRFFQ69G5AA';
    private const ZONE_ID       = '01ABCDEFGHJKMNPTQVZ0123456';

    private function makeTechnicianWithZone(): Employee
    {
        $employee = Employee::hire(
            id:                   new EmployeeIdValueObject(self::TECHNICIAN_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1710034065'),
            firstName:            'Carlos',
            lastName:             'Técnico',
            email:                new EmployeeEmailValueObject('carlos.tec@example.com'),
            phoneNumber:          '0998765432',
            hiredAt:              new \DateTimeImmutable('2023-01-15'),
            profile:              EmployeeProfile::TECHNICIAN,
        );
        $employee->assignToZone(new ServiceZoneIdValueObject(self::ZONE_ID));

        return $employee;
    }

    public function testRemovesTechnicianFromZoneSuccessfully(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $employee = $this->makeTechnicianWithZone();
        $employeeRepo->method('findById')->willReturn($employee);

        $savedEmployee = null;
        $employeeRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Employee $e) use (&$savedEmployee): void {
                $savedEmployee = $e;
            });

        $handler = new RemoveTechnicianFromZoneHandler($employeeRepo);
        ($handler)(new RemoveTechnicianFromZoneCommand(technicianId: self::TECHNICIAN_ID));

        self::assertNotNull($savedEmployee);
        self::assertNull($savedEmployee->serviceZoneId());
    }

    public function testThrowsWhenTechnicianNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn(null);

        $handler = new RemoveTechnicianFromZoneHandler($employeeRepo);

        $this->expectException(EmployeeNotFoundException::class);
        $this->expectExceptionMessage('Empleado con ID "' . self::TECHNICIAN_ID . '" no encontrado.');

        ($handler)(new RemoveTechnicianFromZoneCommand(technicianId: self::TECHNICIAN_ID));
    }
}
