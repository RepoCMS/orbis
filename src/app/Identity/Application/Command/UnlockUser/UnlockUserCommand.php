<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\UnlockUser;

final readonly class UnlockUserCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
