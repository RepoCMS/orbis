<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\FindServiceZoneById;

final readonly class FindServiceZoneByIdQuery
{
    public function __construct(
        public string $serviceZoneId,
    ) {
    }
}
