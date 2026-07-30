<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListEvidences;

final readonly class ListEvidencesQuery
{
    public function __construct(
        public string $ticketId,
    ) {}
}
