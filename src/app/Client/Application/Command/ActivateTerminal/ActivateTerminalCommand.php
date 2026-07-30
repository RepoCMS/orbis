<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ActivateTerminal;

final readonly class ActivateTerminalCommand
{
    public function __construct(
        public string $terminalId,
    ) {
    }
}
