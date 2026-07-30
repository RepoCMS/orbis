<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\Response;

final readonly class ApiTokenResponse
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken = null,
        public string $tokenType = 'Bearer',
        public int $expiresIn = 3600,
    ) {
    }
}
