<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ActivateRole;

final readonly class ActivateRoleCommand
{
    public function __construct(
        public string $roleId,
    ) {
    }
}
