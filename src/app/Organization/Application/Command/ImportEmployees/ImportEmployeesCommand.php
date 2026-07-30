<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ImportEmployees;

final readonly class ImportEmployeesCommand
{
    public function __construct(
        public string $filePath,
        public bool $dryRun = false,
    ) {}
}
