<?php

declare(strict_types=1);

namespace App\Client\Application\Query\ListContactsByClient;

use App\Client\Domain\ValueObjects\ClientIdValueObject;

final readonly class ListContactsByClientQuery
{
    public function __construct(
        public ClientIdValueObject $clientId,
    ) {
    }
}
