<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindTerminalAssignmentById;

final readonly class FindTerminalAssignmentByIdQuery
{
    public function __construct(
        public string $assignmentId,
    ) {
    }
}
