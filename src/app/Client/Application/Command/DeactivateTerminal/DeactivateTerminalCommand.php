<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeactivateTerminal;

final readonly class DeactivateTerminalCommand
{
    public function __construct(
        public string $terminalId,
    ) {
    }
}
