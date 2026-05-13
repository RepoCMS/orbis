<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;

interface RoleRepositoryInterface
{
    public function findById(RoleIdValueObject $id): ?Role;

    public function findByName(RoleNameValueObject $name): ?Role;

    /**
     * @return Role[]
     */
    public function findAll(): array;

    public function save(Role $role): void;

    public function delete(RoleIdValueObject $id): void;
}
