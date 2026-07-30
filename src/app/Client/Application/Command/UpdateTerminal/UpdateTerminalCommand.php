<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateTerminal;

final readonly class UpdateTerminalCommand
{
    public function __construct(
        public string $terminalId,
        public string $serial,
        public string $vendor,
        public string $funcionality,
        public string $type,
        public ?string $atmModelCode = null,
    ) {
    }
}
