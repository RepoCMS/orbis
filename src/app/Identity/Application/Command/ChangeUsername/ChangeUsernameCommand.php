<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ChangeUsername;

final readonly class ChangeUsernameCommand
{
    public function __construct(
        public string $userId,
        public string $newUsername,
    ) {
    }
}
