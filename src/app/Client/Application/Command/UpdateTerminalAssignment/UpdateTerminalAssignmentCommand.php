<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateTerminalAssignment;

final readonly class UpdateTerminalAssignmentCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public string $idAssignedByClient,
        public string $locationId,
    ) {
    }
}
