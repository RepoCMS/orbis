<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repositories;

use App\Organization\Domain\Entities\VacationRequest;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;

interface VacationRequestRepositoryInterface
{
    public function save(VacationRequest $request): void;

    public function findById(VacationRequestIdValueObject $id): ?VacationRequest;

    /**
     * @return VacationRequest[]
     */
    public function findPendingByEmployee(EmployeeIdValueObject $employeeId): array;

    /**
     * @return VacationRequest[]
     */
    public function findAllByEmployee(EmployeeIdValueObject $employeeId): array;
}
