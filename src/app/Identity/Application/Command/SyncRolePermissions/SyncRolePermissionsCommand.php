<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\SyncRolePermissions;

final readonly class SyncRolePermissionsCommand
{
    /**
     * @param string[] $permissionIds IDs de los permisos que deben quedar asignados al rol
     */
    public function __construct(
        public string $roleId,
        public array $permissionIds,
    ) {
    }
}
