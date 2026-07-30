<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListRepairConsumables;

final readonly class ListRepairConsumablesQuery
{
    public function __construct(
        public string $repairId,
    ) {
    }
}
