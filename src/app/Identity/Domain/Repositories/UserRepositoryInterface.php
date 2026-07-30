<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;

interface UserRepositoryInterface
{
    public function findById(UserIdValueObject $id): ?User;

    public function findByUsername(UsernameValueObject $username): ?User;

    /**
     * @return User[]
     */
    public function findAll(): array;

    public function countByRoleId(RoleIdValueObject $roleId): int;

    public function save(User $user): void;

    public function delete(UserIdValueObject $id): void;
}
