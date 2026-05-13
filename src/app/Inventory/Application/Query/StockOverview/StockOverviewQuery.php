<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\StockOverview;

final readonly class StockOverviewQuery
{
    public function __construct(
        public ?string $locationId  = null,
        public ?string $productId   = null,
        public ?string $status      = null,
        /** 'serialized' | 'nonserialized' | null (both) */
        public ?string $type        = null,
        public ?string $search      = null,
        public int     $page        = 1,
        public int     $perPage     = 30,
    ) {}
}
