<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\Response;

use App\Identity\Domain\Entities\Permission;

final readonly class PermissionResponse
{
    public function __construct(
        public string $id,
        public string $key,
        public string $description,
    ) {
    }

    public static function fromEntity(Permission $permission): self
    {
        return new self(
            id: (string) $permission->id(),
            key: (string) $permission->key(),
            description: $permission->description(),
        );
    }
}
