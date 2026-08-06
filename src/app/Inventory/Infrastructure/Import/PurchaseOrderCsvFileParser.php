<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Import;

use App\Shared\Application\Import\FileParserInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;

final readonly class PurchaseOrderCsvFileParser implements FileParserInterface
{
    private const EXPECTED_HEADERS = [
        'reference',
        'notes',
        'items',
    ];

    public function __construct(
        private CsvFileParser $csvParser,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function parse(string $filePath, int $maxRows = 100): array
    {
        return $this->csvParser->parse($filePath, $maxRows);
    }

    /** @return string[] */
    public function getExpectedHeaders(): array
    {
        return self::EXPECTED_HEADERS;
    }

    /** @return string[] */
    public function validateHeaders(array $headers): array
    {
        return CsvFileParser::validateHeaders(self::EXPECTED_HEADERS, $headers);
    }
}
