<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Import;

use App\Shared\Application\Import\RowValidatorInterface;

final readonly class TerminalRowValidator implements RowValidatorInterface
{
    private const RUC_PATTERN = '/^\d{11,15}$/';
    private const VALID_VENDORS = ['hyosung', 'diebold', 'ncr', 'grg'];
    private const VALID_FUNCTIONALITIES = ['dispenser', 'recycler', 'multinfunction', 'counter'];
    private const VALID_TYPES = ['lobby', 'ttw'];

    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $ruc = trim($row['ruc'] ?? '');
        if ($ruc === '') {
            $errors[] = sprintf('Fila %d: RUC es obligatorio', $rowNumber);
        } elseif (!preg_match(self::RUC_PATTERN, $ruc)) {
            $errors[] = sprintf('Fila %d: RUC inválido (debe ser numérico, 11-15 dígitos)', $rowNumber);
        }

        $serial = trim($row['serial'] ?? '');
        if ($serial === '') {
            $errors[] = sprintf('Fila %d: Serial es obligatorio', $rowNumber);
        }

        $vendor = trim($row['vendor'] ?? '');
        if ($vendor === '') {
            $errors[] = sprintf('Fila %d: Fabricante es obligatorio', $rowNumber);
        } elseif (!in_array($vendor, self::VALID_VENDORS, true)) {
            $errors[] = sprintf('Fila %d: Fabricante inválido (%s). Válidos: %s', $rowNumber, $vendor, implode(', ', self::VALID_VENDORS));
        }

        $funcionality = trim($row['funcionality'] ?? '');
        if ($funcionality === '') {
            $errors[] = sprintf('Fila %d: Funcionalidad es obligatoria', $rowNumber);
        } elseif (!in_array($funcionality, self::VALID_FUNCTIONALITIES, true)) {
            $errors[] = sprintf('Fila %d: Funcionalidad inválida (%s). Válidas: %s', $rowNumber, $funcionality, implode(', ', self::VALID_FUNCTIONALITIES));
        }

        $type = trim($row['type'] ?? '');
        if ($type === '') {
            $errors[] = sprintf('Fila %d: Tipo es obligatorio', $rowNumber);
        } elseif (!in_array($type, self::VALID_TYPES, true)) {
            $errors[] = sprintf('Fila %d: Tipo inválido (%s). Válidos: %s', $rowNumber, $type, implode(', ', self::VALID_TYPES));
        }

        return $errors;
    }
}
