<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetCatCodeComponents;

final readonly class GetCatCodeComponentsQuery
{
    public function __construct(
        public string $ticketId,
    ) {
    }
}
