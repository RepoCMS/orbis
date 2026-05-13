<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RevokePermissionFromRole;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;

final readonly class RevokePermissionFromRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function __invoke(RevokePermissionFromRoleCommand $command): void
    {
        $roleId = new RoleIdValueObject($command->roleId);
        $role = $this->roleRepository->findById($roleId)
            ?? throw RoleNotFoundException::withId($command->roleId);

        $permissionId = new PermissionIdValueObject($command->permissionId);

        $role->revokePermission($permissionId);

        $this->roleRepository->save($role);
    }
}
