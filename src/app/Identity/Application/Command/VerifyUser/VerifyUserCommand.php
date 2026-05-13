<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\VerifyUser;

final readonly class VerifyUserCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
