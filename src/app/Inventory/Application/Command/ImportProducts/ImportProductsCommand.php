<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ImportProducts;

final readonly class ImportProductsCommand
{
    public function __construct(
        public string $filePath,
        public bool $dryRun = false,
    ) {
    }
}
