<?php

declare(strict_types=1);

namespace App\Client\Application\Command\RegisterTerminalAssignment;

final readonly class RegisterTerminalAssignmentCommand
{
    public function __construct(
        public string $terminalId,
        public string $locationId,
        public string $name,
        public string $idAssignedByClient,
    ) {
    }
}
