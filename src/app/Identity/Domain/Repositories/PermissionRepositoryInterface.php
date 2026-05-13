<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;

interface PermissionRepositoryInterface
{
    public function findById(PermissionIdValueObject $id): ?Permission;

    public function findByKey(PermissionKeyValueObject $key): ?Permission;

    /**
     * @return Permission[]
     */
    public function findAll(): array;

    /**
     * @param PermissionIdValueObject[] $ids
     * @return Permission[]
     */
    public function findByIds(array $ids): array;

    public function save(Permission $permission): void;

    public function delete(PermissionIdValueObject $id): void;
}
