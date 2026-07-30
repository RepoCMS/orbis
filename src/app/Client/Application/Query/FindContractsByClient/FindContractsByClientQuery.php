<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindContractsByClient;

final readonly class FindContractsByClientQuery
{
    public function __construct(
        public string $clientId,
    ) {
    }
}
