<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DefinePermission;

final readonly class DefinePermissionCommand
{
    public function __construct(
        public string $permissionId,
        public string $key,
        public string $description,
    ) {
    }
}
