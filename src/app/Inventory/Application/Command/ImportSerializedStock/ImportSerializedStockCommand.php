<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ImportSerializedStock;

final readonly class ImportSerializedStockCommand
{
    public function __construct(
        public string $filePath,
        public bool $dryRun = false,
    ) {}
}
