<?php

declare(strict_types=1);

namespace App\Shared\Application\Import;

interface FileParserInterface
{
    /** @return array<int, array<string, string>> */
    public function parse(string $filePath, int $maxRows = 100): array;

    /** @return string[] */
    public function getExpectedHeaders(): array;

    /** @return string[] */
    public function validateHeaders(array $headers): array;
}
