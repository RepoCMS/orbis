<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\SubLocation;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;

interface SubLocationRepositoryInterface
{
    public function findById(SubLocationIdValueObject $id): ?SubLocation;

    /** @return SubLocation[] */
    public function findByLocationId(string $locationId): array;

    /** @return SubLocation[] */
    public function findByParentId(string $parentId): array;

    /** @return SubLocation[] */
    public function findRootsByLocationId(string $locationId): array;

    public function save(SubLocation $subLocation): void;

    public function delete(SubLocationIdValueObject $id): void;
}
