<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ImportClients;

final readonly class ImportClientsCommand
{
    public function __construct(
        public string $filePath,
        public bool $dryRun = false,
    ) {}
}
