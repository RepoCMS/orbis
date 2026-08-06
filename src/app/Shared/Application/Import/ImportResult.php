<?php

declare(strict_types=1);

namespace App\Shared\Application\Import;

final readonly class ImportResult
{
    /** @param array<int, string> $errors */
    public function __construct(
        public int   $imported,
        public int   $errors,
        public int   $skipped,
        public array $errorDetail = [],
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->errors === 0 && $this->skipped === 0;
    }

    public function hasPartialSuccess(): bool
    {
        return $this->imported > 0 && ($this->errors > 0 || $this->skipped > 0);
    }
}
