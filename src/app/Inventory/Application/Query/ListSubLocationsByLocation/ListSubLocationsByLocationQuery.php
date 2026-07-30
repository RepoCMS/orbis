<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListSubLocationsByLocation;

final readonly class ListSubLocationsByLocationQuery
{
    public function __construct(
        public string $locationId,
    ) {
    }
}
