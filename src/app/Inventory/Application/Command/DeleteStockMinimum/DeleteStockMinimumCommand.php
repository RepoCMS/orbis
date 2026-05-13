<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DeleteStockMinimum;

final readonly class DeleteStockMinimumCommand
{
    public function __construct(
        public string $stockMinimumId,
    ) {
    }
}
