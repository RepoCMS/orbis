<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DeleteRole;

final readonly class DeleteRoleCommand
{
    public function __construct(
        public string $roleId,
    ) {
    }
}
