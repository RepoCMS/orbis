<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindRoleBySlug;

final readonly class FindRoleBySlugQuery
{
    public function __construct(
        public string $slug,
    ) {
    }
}
