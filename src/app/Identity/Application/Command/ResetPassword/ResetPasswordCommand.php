<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ResetPassword;

final readonly class ResetPasswordCommand
{
    public function __construct(
        public string $token,
        public string $newPassword,
    ) {
    }
}
