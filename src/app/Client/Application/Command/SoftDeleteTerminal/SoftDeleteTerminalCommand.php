<?php

declare(strict_types=1);

namespace App\Client\Application\Command\SoftDeleteTerminal;

final readonly class SoftDeleteTerminalCommand
{
    public function __construct(
        public string $terminalId,
        public string $reason = '',
    ) {
    }
}
