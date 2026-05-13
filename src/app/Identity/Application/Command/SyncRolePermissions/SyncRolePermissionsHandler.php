<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\SyncRolePermissions;

use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;

/**
 * Reemplaza el conjunto completo de permisos de un rol.
 *
 * Estrategia:
 *  1. Revoca todos los permisos actuales del rol.
 *  2. Asigna los nuevos IDs recibidos.
 *  3. Guarda — el repositorio hace DELETE+INSERT en role_permissions.
 *
 * Es idempotente: llamarlo dos veces con los mismos IDs produce el mismo resultado.
 */
final readonly class SyncRolePermissionsHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function __invoke(SyncRolePermissionsCommand $command): void
    {
        $roleId = new RoleIdValueObject($command->roleId);
        $role = $this->roleRepository->findById($roleId)
            ?? throw RoleNotFoundException::withId($command->roleId);

        // Revocar todos los permisos actuales
        foreach ($role->permissionIds() as $currentId) {
            $role->revokePermission($currentId);
        }

        // Asignar los nuevos, validando que cada uno exista
        foreach ($command->permissionIds as $rawId) {
            $permissionId = new PermissionIdValueObject($rawId);

            if ($this->permissionRepository->findById($permissionId) === null) {
                throw PermissionNotFoundException::withId($rawId);
            }

            $role->assignPermission($permissionId);
        }

        $this->roleRepository->save($role);
    }
}
