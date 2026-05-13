<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DeactivateUser;

final readonly class DeactivateUserCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
