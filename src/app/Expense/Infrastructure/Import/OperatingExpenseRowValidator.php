<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Import;

use App\Expense\Domain\Enums\ExpenseItemType;
use App\Shared\Application\Import\RowValidatorInterface;

final readonly class OperatingExpenseRowValidator implements RowValidatorInterface
{
    private const DATE_PATTERN = '/^\d{4}-\d{2}-\d{2}$/';

    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $employeeId = trim($row['empleado_id'] ?? '');
        if ($employeeId === '') {
            $errors[] = sprintf('Fila %d: ID del empleado es obligatorio', $rowNumber);
        } elseif (strlen($employeeId) > 26) {
            $errors[] = sprintf('Fila %d: ID del empleado inválido', $rowNumber);
        }

        $tipo = trim($row['tipo'] ?? '');
        if ($tipo === '') {
            $errors[] = sprintf('Fila %d: Tipo de gasto es obligatorio', $rowNumber);
        } else {
            try {
                ExpenseItemType::from($tipo);
            } catch (\ValueError) {
                $validTypes = implode(', ', array_map(fn(ExpenseItemType $t) => $t->value, ExpenseItemType::cases()));
                $errors[] = sprintf('Fila %d: Tipo de gasto "%s" inválido. Válidos: %s', $rowNumber, $tipo, $validTypes);
            }
        }

        $monto = trim($row['monto'] ?? '');
        if ($monto === '') {
            $errors[] = sprintf('Fila %d: Monto es obligatorio', $rowNumber);
        } elseif (!is_numeric($monto) || (float) $monto <= 0) {
            $errors[] = sprintf('Fila %d: Monto debe ser un número positivo', $rowNumber);
        }

        $fecha = trim($row['fecha'] ?? '');
        if ($fecha === '') {
            $errors[] = sprintf('Fila %d: Fecha es obligatoria', $rowNumber);
        } elseif (!preg_match(self::DATE_PATTERN, $fecha)) {
            $errors[] = sprintf('Fila %d: Fecha inválida (use YYYY-MM-DD)', $rowNumber);
        }

        return $errors;
    }
}
