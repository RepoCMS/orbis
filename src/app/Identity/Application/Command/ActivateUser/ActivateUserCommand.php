<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ActivateUser;

final readonly class ActivateUserCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
