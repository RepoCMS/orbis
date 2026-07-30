<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Import;

use App\Shared\Application\Import\RowValidatorInterface;

final readonly class ClientRowValidator implements RowValidatorInterface
{
    private const RUC_PATTERN = '/^\d{11,15}$/';

    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        if (empty(trim($row['ruc'] ?? ''))) {
            $errors[] = sprintf('Fila %d: RUC es obligatorio', $rowNumber);
        } elseif (!preg_match(self::RUC_PATTERN, trim($row['ruc']))) {
            $errors[] = sprintf('Fila %d: RUC inválido (debe ser numérico, 11-15 dígitos)', $rowNumber);
        }

        if (empty(trim($row['nombre'] ?? ''))) {
            $errors[] = sprintf('Fila %d: Nombre es obligatorio', $rowNumber);
        }

        if (!empty(trim($row['email'] ?? '')) && !filter_var(trim($row['email']), FILTER_VALIDATE_EMAIL)) {
            $errors[] = sprintf('Fila %d: Email inválido', $rowNumber);
        }

        return $errors;
    }
}
