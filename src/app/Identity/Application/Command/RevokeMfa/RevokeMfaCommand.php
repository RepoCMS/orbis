<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RevokeMfa;

final readonly class RevokeMfaCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
