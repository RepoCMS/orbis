<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindPermissionById;

final readonly class FindPermissionByIdQuery
{
    public function __construct(
        public string $permissionId,
    ) {
    }
}
