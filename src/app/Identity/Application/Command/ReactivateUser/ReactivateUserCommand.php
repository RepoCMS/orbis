<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ReactivateUser;

final readonly class ReactivateUserCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
