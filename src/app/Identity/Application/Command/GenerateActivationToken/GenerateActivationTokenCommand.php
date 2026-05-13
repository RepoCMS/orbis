<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\GenerateActivationToken;

final readonly class GenerateActivationTokenCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
