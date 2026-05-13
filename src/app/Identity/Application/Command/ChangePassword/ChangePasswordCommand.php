<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ChangePassword;

final readonly class ChangePasswordCommand
{
    public function __construct(
        public string $userId,
        public string $newRawPassword,
    ) {
    }
}
