<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RemoveTechnicianFromZone;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

final readonly class RemoveTechnicianFromZoneHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function __invoke(RemoveTechnicianFromZoneCommand $command): void
    {
        $employeeId = new EmployeeIdValueObject($command->technicianId);
        $employee   = $this->employeeRepository->findById($employeeId)
            ?? throw EmployeeNotFoundException::withId($command->technicianId);

        $employee->removeFromZone();
        $this->employeeRepository->save($employee);
    }
}
