<?php

declare(strict_types=1);

namespace App\Client\Application\Query\ListTerminalAssignments;

final readonly class ListTerminalAssignmentsQuery
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
