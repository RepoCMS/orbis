<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ImportModelParts;

final readonly class ImportModelPartsCommand
{
    public function __construct(
        public string $filePath,
        public bool $dryRun = false,
    ) {}
}
