<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Import;

use App\Shared\Application\Import\FileParserInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;

final readonly class OperatingExpenseCsvFileParser implements FileParserInterface
{
    private const EXPECTED_HEADERS = [
        'empleado_id',
        'tipo',
        'monto',
        'fecha',
        'ruta_evidencia',
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
