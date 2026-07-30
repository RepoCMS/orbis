<?php

declare(strict_types=1);

namespace App\Shared\Application\Import;

interface RowValidatorInterface
{
    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array;
}
