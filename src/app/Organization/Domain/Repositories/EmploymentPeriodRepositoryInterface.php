<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repositories;

use App\Organization\Domain\Entities\EmploymentPeriod;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmploymentPeriodIdValueObject;

interface EmploymentPeriodRepositoryInterface
{
    public function save(EmploymentPeriod $period): void;

    public function findById(EmploymentPeriodIdValueObject $id): ?EmploymentPeriod;

    /**
     * Retorna el período activo (status ACTIVE) de un empleado, o null.
     */
    public function findActiveByEmployee(EmployeeIdValueObject $employeeId): ?EmploymentPeriod;

    /**
     * Retorna todos los períodos de empleo de un empleado, ordenados por startedAt DESC.
     *
     * @return EmploymentPeriod[]
     */
    public function findAllByEmployee(EmployeeIdValueObject $employeeId): array;
}
