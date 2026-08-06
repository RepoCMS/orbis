<?php

declare(strict_types=1);

namespace App\Client\Application\Command\RegisterTerminal;

final readonly class RegisterTerminalCommand
{
    public function __construct(
        public string $clientId,
        public string $serial,
        public string $vendor,
        public string $funcionality,
        public string $type,
    ) {
    }
}
