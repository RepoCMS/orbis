<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindContactsByClient;

final readonly class FindContactsByClientQuery
{
    public function __construct(
        public string $clientId,
    ) {
    }
}
