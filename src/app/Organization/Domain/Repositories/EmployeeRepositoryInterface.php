<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repositories;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

interface EmployeeRepositoryInterface
{
    public function findById(EmployeeIdValueObject $id): ?Employee;

    public function findByUserId(UserIdValueObject $userId): ?Employee;

    public function findByEmail(string $email): ?Employee;

    /**
     * @return Employee[]
     */
    public function findAll(): array;

    /**
     * @return Employee[]
     */
    public function findByFilters(
        ?EmployeeProfile $profile = null,
        ?EmployeeStatus $status = null,
        ?string $search = null,
        ?string $serviceZoneId = null,
        int $page = 1,
        ?int $limit = null,
    ): array;

    /**
     * @return int
     */
    public function countByFilters(
        ?EmployeeProfile $profile = null,
        ?EmployeeStatus $status = null,
        ?string $search = null,
        ?string $serviceZoneId = null,
    ): int;

    public function save(Employee $employee): void;

    public function delete(EmployeeIdValueObject $id): void;
}
