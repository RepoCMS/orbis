<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RevokePermissionFromRole;

final readonly class RevokePermissionFromRoleCommand
{
    public function __construct(
        public string $roleId,
        public string $permissionId,
    ) {
    }
}
