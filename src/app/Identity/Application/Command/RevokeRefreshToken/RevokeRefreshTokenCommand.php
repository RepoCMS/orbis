<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RevokeRefreshToken;

final readonly class RevokeRefreshTokenCommand
{
    public function __construct(
        public string $tokenHash,
    ) {
    }
}
