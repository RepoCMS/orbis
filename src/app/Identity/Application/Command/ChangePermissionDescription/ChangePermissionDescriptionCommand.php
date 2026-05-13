<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ChangePermissionDescription;

final readonly class ChangePermissionDescriptionCommand
{
    public function __construct(
        public string $permissionId,
        public string $newDescription,
    ) {
    }
}
