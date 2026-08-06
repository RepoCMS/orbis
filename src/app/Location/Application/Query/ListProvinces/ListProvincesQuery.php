<?php

declare(strict_types=1);

namespace App\Location\Application\Query\ListProvinces;

final readonly class ListProvincesQuery
{
    public function __construct(
        public ?string $regionId = null,
    ) {
    }
}
