<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repositories;

use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

interface EmployeeRepositoryInterface
{
    public function findById(EmployeeIdValueObject $id): ?Employee;

    public function findByUserId(UserIdValueObject $userId): ?Employee;

    /**
     * @return Employee[]
     */
    public function findAll(): array;

    public function save(Employee $employee): void;

    public function delete(EmployeeIdValueObject $id): void;
}
