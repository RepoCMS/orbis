<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\AssignTechnicianToZone;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

final readonly class AssignTechnicianToZoneHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private ServiceZoneRepositoryInterface $zoneRepository,
    ) {
    }

    public function __invoke(AssignTechnicianToZoneCommand $command): void
    {
        $employeeId = new EmployeeIdValueObject($command->technicianId);
        $zoneId     = new ServiceZoneIdValueObject($command->serviceZoneId);

        $employee = $this->employeeRepository->findById($employeeId)
            ?? throw EmployeeNotFoundException::withId($command->technicianId);

        // Validates profile; throws EmployeeProfileException if not TECHNICIAN
        $this->zoneRepository->findById($zoneId)
            ?? throw ServiceZoneNotFoundException::withId($command->serviceZoneId);

        $employee->assignToZone($zoneId);
        $this->employeeRepository->save($employee);
    }
}
