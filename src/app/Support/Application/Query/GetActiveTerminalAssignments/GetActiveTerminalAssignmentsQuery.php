<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetActiveTerminalAssignments;

final readonly class GetActiveTerminalAssignmentsQuery
{
    public function __construct(
        public string $clientId,
    ) {
    }
}
