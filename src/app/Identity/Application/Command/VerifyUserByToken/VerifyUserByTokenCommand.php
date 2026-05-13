<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\VerifyUserByToken;

final readonly class VerifyUserByTokenCommand
{
    public function __construct(
        public string $plainToken,
    ) {
    }
}
