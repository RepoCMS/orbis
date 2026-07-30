<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;

interface LocationRepositoryInterface
{
    public function findById(LocationIdValueObject $id): ?Location;

    /** @return Location[] */
    public function findAll(): array;

    /** @return Location[] */
    public function findByEmployeeId(string $employeeId): array;

    /** @return Location[] */
    public function findByType(\App\Inventory\Domain\Enums\LocationType $type): array;

    public function save(Location $location): void;

    public function delete(LocationIdValueObject $id): void;
}
