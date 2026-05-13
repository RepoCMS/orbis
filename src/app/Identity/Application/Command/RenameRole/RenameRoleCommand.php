<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RenameRole;

final readonly class RenameRoleCommand
{
    public function __construct(
        public string $roleId,
        public string $newName,
    ) {
    }
}
