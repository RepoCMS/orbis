<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\Response;

use App\Identity\Domain\Entities\Role;

final readonly class RoleResponse
{
    /**
     * @param string[] $permissionIds
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public array $permissionIds,
    ) {
    }

    public static function fromEntity(Role $role): self
    {
        return new self(
            id: (string) $role->id(),
            name: (string) $role->name(),
            status: $role->status()->value,
            permissionIds: array_map(
                static fn($pid) => (string) $pid,
                $role->permissionIds(),
            ),
        );
    }
}
