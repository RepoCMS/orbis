<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RecordFailedLogin;

final readonly class RecordFailedLoginCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
