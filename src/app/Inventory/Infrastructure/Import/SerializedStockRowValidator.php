<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Import;

use App\Shared\Application\Import\RowValidatorInterface;

final readonly class SerializedStockRowValidator implements RowValidatorInterface
{
    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $productCode = trim($row['product_code'] ?? '');
        if ($productCode === '') {
            $errors[] = sprintf('Fila %d: Código de producto es obligatorio', $rowNumber);
        }

        $serial = trim($row['serial_number'] ?? '');
        if ($serial === '') {
            $errors[] = sprintf('Fila %d: Número de serie es obligatorio', $rowNumber);
        } elseif (strlen($serial) > 100) {
            $errors[] = sprintf('Fila %d: Número de serie no puede exceder 100 caracteres', $rowNumber);
        }

        $locationId = trim($row['location_id'] ?? '');
        if ($locationId === '') {
            $errors[] = sprintf('Fila %d: ID de ubicación es obligatorio', $rowNumber);
        } elseif (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', strtoupper($locationId))) {
            $errors[] = sprintf('Fila %d: ID de ubicación inválido (debe ser un ULID de 26 caracteres)', $rowNumber);
        }

        return $errors;
    }
}
