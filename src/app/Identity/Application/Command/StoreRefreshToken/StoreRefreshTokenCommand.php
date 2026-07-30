<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\StoreRefreshToken;

final readonly class StoreRefreshTokenCommand
{
    public function __construct(
        public string $userId,
        public string $plainToken,
    ) {
    }
}
