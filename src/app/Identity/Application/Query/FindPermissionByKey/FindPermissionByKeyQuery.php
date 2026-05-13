<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindPermissionByKey;

final readonly class FindPermissionByKeyQuery
{
    public function __construct(
        public string $key,
    ) {
    }
}
