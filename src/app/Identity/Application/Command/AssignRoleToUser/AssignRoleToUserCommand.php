<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\AssignRoleToUser;

final readonly class AssignRoleToUserCommand
{
    public function __construct(
        public string $userId,
        public string $roleId,
    ) {
    }
}
