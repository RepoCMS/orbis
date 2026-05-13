<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListStockMinimums;

final readonly class ListStockMinimumsQuery
{
    public function __construct(
        public ?string $locationId = null,
        public int     $page       = 1,
        public int     $perPage    = 20,
    ) {
    }
}
