<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\ImportOperatingExpenses;

final readonly class ImportOperatingExpensesCommand
{
    public function __construct(
        public string $filePath,
        public bool $dryRun = false,
    ) {
    }
}
