<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Import;

use Symfony\Component\Serializer\Encoder\CsvEncoder;

final readonly class CsvFileParser
{
    /** @return array<int, array<string, mixed>> */
    public function parse(string $filePath, int $maxRows = 100): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException(sprintf('No se pudo leer el archivo: %s', $filePath));
        }

        $context = [
            CsvEncoder::DELIMITER_KEY => ',',
            CsvEncoder::ENCLOSURE_KEY => '"',
        ];

        $encoder = new CsvEncoder();
        $rows = $encoder->decode($content, 'csv', $context);

        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        return array_slice($rows, 0, $maxRows);
    }

    /** @return string[] */
    public static function validateHeaders(array $expected, array $actual): array
    {
        return array_values(array_diff($expected, $actual));
    }
}
