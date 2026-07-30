<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListPhysicalCounts;

final readonly class ListPhysicalCountsQuery
{
    public function __construct(
        public ?string $locationId = null,
        public ?string $status = null,
    ) {
    }
}
