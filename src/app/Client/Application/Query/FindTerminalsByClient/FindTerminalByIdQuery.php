<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindTerminalsByClient;

final readonly class FindTerminalByIdQuery
{
    public function __construct(
        public string $terminalId,
    ) {
    }
}
