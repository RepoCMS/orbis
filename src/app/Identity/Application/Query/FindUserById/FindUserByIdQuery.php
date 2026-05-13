<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindUserById;

final readonly class FindUserByIdQuery
{
    public function __construct(
        public string $userId,
    ) {
    }
}
