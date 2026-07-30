<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ActivateTerminalAssignment;

final readonly class ActivateTerminalAssignmentCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
