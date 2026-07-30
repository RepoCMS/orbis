<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RequestPasswordReset;

final readonly class RequestPasswordResetCommand
{
    public function __construct(
        public string $email,
    ) {
    }
}
