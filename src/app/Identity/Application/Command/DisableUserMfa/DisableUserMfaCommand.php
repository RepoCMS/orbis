<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DisableUserMfa;

final readonly class DisableUserMfaCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
