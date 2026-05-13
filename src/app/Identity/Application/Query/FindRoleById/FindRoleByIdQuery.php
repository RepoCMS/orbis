<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindRoleById;

final readonly class FindRoleByIdQuery
{
    public function __construct(
        public string $roleId,
    ) {
    }
}
