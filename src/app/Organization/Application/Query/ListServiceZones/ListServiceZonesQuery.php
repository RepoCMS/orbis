<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListServiceZones;

final readonly class ListServiceZonesQuery
{
    public function __construct(
        public bool $onlyActive = false,
    ) {
    }
}
