<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ImportPurchaseOrders;

final readonly class ImportPurchaseOrdersCommand
{
    public function __construct(
        public string $filePath,
        public string $createdBy,
        public bool $dryRun = false,
    ) {
    }
}
