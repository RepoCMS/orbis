<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repositories;

use App\Organization\Domain\Entities\ServiceZoneAssignment;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneAssignmentIdValueObject;

interface ServiceZoneAssignmentRepositoryInterface
{
    public function save(ServiceZoneAssignment $assignment): void;

    public function findById(ServiceZoneAssignmentIdValueObject $id): ?ServiceZoneAssignment;

    /**
     * @return ServiceZoneAssignment[]
     */
    public function findByEmployee(EmployeeIdValueObject $employeeId): array;
}
