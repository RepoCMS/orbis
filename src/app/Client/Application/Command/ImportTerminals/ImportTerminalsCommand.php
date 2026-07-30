<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ImportTerminals;

final readonly class ImportTerminalsCommand
{
    public function __construct(
        public string $filePath,
        public bool $dryRun = false,
    ) {}
}
