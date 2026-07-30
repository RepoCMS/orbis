<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\AssignTechnicianToZone;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Entities\ServiceZoneAssignment;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\ServiceZoneAssignmentRepositoryInterface;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneAssignmentIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class AssignTechnicianToZoneHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private ServiceZoneRepositoryInterface $zoneRepository,
        private ServiceZoneAssignmentRepositoryInterface $assignmentRepository,
        private IdGeneratorInterface $idGenerator,
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

        $previousZoneId = $employee->serviceZoneId();

        $employee->assignToZone($zoneId);
        $this->employeeRepository->save($employee);

        // Registrar historial de asignación
        $assignment = ServiceZoneAssignment::create(
            id: new ServiceZoneAssignmentIdValueObject($this->idGenerator->generate()),
            employeeId: $employeeId,
            newZoneId: $zoneId,
            previousZoneId: $previousZoneId,
            assignedByUserId: $command->assignedByUserId,
        );

        $this->assignmentRepository->save($assignment);
    }
}
