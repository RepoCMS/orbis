<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListStockAlerts;

final readonly class ListStockAlertsQuery
{
    public function __construct(
        public ?string $locationId = null,
    ) {
    }
}
