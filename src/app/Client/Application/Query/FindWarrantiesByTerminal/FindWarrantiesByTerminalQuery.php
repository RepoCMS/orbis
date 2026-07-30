<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindWarrantiesByTerminal;

final readonly class FindWarrantiesByTerminalQuery
{
    public function __construct(
        public string $terminalId,
    ) {
    }
}
