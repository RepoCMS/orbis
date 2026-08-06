<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ImportTerminalModels;

final readonly class ImportTerminalModelsCommand
{
    public function __construct(
        public string $filePath,
        public bool $dryRun = false,
    ) {
    }
}
