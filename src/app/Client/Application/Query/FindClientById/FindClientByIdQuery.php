<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindClientById;

final readonly class FindClientByIdQuery
{
    public function __construct(
        public string $clientId,
    ) {
    }
}
