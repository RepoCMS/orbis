<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DeactivateRole;

final readonly class DeactivateRoleCommand
{
    public function __construct(
        public string $roleId,
    ) {
    }
}
