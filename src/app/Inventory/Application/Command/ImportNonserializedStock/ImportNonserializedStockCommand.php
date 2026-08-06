<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ImportNonserializedStock;

final readonly class ImportNonserializedStockCommand
{
    public function __construct(
        public string $filePath,
        public bool $dryRun = false,
    ) {
    }
}
