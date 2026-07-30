<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\PhysicalCount;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;

interface PhysicalCountRepositoryInterface
{
    public function findById(PhysicalCountIdValueObject $id): ?PhysicalCount;

    /** @return PhysicalCount[] */
    public function findAll(): array;

    /** @return PhysicalCount[] */
    public function findByLocationId(string $locationId): array;

    public function save(PhysicalCount $physicalCount): void;

    public function delete(PhysicalCountIdValueObject $id): void;
}
