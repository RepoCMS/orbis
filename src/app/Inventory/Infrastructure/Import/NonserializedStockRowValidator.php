<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Import;

use App\Shared\Application\Import\RowValidatorInterface;

final readonly class NonserializedStockRowValidator implements RowValidatorInterface
{
    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $productCode = trim($row['product_code'] ?? '');
        if ($productCode === '') {
            $errors[] = sprintf('Fila %d: Código de producto es obligatorio', $rowNumber);
        }

        $locationId = trim($row['location_id'] ?? '');
        if ($locationId === '') {
            $errors[] = sprintf('Fila %d: ID de ubicación es obligatorio', $rowNumber);
        } elseif (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', strtoupper($locationId))) {
            $errors[] = sprintf('Fila %d: ID de ubicación inválido (debe ser un ULID de 26 caracteres)', $rowNumber);
        }

        $quantity = trim($row['quantity'] ?? '');
        if ($quantity === '') {
            $errors[] = sprintf('Fila %d: Cantidad es obligatoria', $rowNumber);
        } elseif (!ctype_digit($quantity) || (int) $quantity <= 0) {
            $errors[] = sprintf('Fila %d: Cantidad debe ser un número entero positivo', $rowNumber);
        }

        return $errors;
    }
}
