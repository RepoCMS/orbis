<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeactivateTerminalAssignment;

final readonly class DeactivateTerminalAssignmentCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
