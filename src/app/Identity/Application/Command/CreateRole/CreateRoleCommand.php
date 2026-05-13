<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\CreateRole;

final readonly class CreateRoleCommand
{
    /**
     * @param string[] $permissionIds
     */
    public function __construct(
        public string $name,
        public array $permissionIds = [],
    ) {
    }
}
