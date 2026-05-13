<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindRoleByName;

final readonly class FindRoleByNameQuery
{
    public function __construct(
        public string $name,
    ) {
    }
}
