<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindUserByUsername;

final readonly class FindUserByUsernameQuery
{
    public function __construct(
        public string $username,
    ) {
    }
}
