<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\WriteOffNonserializedStock;

final readonly class WriteOffNonserializedStockCommand
{
    public function __construct(
        public string $nonserializedStockId,
        /** 'damage' | 'decommission' */
        public string $action,
    ) {
    }
}
