<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\AssignPermissionToRole;

use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;

final readonly class AssignPermissionToRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function __invoke(AssignPermissionToRoleCommand $command): void
    {
        $roleId = new RoleIdValueObject($command->roleId);
        $role = $this->roleRepository->findById($roleId)
            ?? throw RoleNotFoundException::withId($command->roleId);

        $permissionId = new PermissionIdValueObject($command->permissionId);
        $this->ensurePermissionExists($permissionId);

        $role->assignPermission($permissionId);

        $this->roleRepository->save($role);
    }

    private function ensurePermissionExists(PermissionIdValueObject $permissionId): void
    {
        if ($this->permissionRepository->findById($permissionId) === null) {
            throw PermissionNotFoundException::withId((string) $permissionId);
        }
    }
}
