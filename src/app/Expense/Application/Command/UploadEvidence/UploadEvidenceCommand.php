<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\UploadEvidence;

final readonly class UploadEvidenceCommand
{
    public function __construct(
        public string $expenseId,
        public string $itemId,
        public string $filePath,
    ) {}
}
