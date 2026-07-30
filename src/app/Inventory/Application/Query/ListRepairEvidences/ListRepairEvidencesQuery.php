<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListRepairEvidences;

final readonly class ListRepairEvidencesQuery
{
    public function __construct(
        public string $repairId,
    ) {
    }
}
