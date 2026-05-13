<?php

declare(strict_types=1);

namespace App\Client\Application\Query\ListSitesByClient;

final readonly class ListSitesByClientQuery
{
    public function __construct(
        public string $clientId,
    ) {
    }
}
