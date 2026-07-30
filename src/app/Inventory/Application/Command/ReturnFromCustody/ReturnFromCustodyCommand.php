<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ReturnFromCustody;

final readonly class ReturnFromCustodyCommand
{
    public function __construct(
        public string $custodyLocationId,
        public string $stockItemId,
        public string $returnLocationId,
    ) {
    }
}
