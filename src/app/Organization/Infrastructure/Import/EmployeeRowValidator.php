<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Import;

use App\Shared\Application\Import\RowValidatorInterface;

final readonly class EmployeeRowValidator implements RowValidatorInterface
{
    private const ID_PATTERN = '/^\d{10,13}$/';
    private const VALID_PROFILES = [
        'technician_field', 'supervisor_technician', 'administrative',
        'finance', 'laboratory', 'warehouse', 'analyst', 'manager', 'coordinator',
    ];
    private const DATE_PATTERN = '/^\d{4}-\d{2}-\d{2}$/';

    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $id = trim($row['identificacion'] ?? '');
        if ($id === '') {
            $errors[] = sprintf('Fila %d: Identificación es obligatoria', $rowNumber);
        } elseif (!preg_match(self::ID_PATTERN, $id)) {
            $errors[] = sprintf('Fila %d: Identificación inválida (10-13 dígitos)', $rowNumber);
        }

        if (empty(trim($row['nombres'] ?? ''))) {
            $errors[] = sprintf('Fila %d: Nombres son obligatorios', $rowNumber);
        }

        if (empty(trim($row['apellidos'] ?? ''))) {
            $errors[] = sprintf('Fila %d: Apellidos son obligatorios', $rowNumber);
        }

        $email = trim($row['email'] ?? '');
        if ($email === '') {
            $errors[] = sprintf('Fila %d: Email es obligatorio', $rowNumber);
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = sprintf('Fila %d: Email inválido', $rowNumber);
        }

        if (empty(trim($row['telefono'] ?? ''))) {
            $errors[] = sprintf('Fila %d: Teléfono es obligatorio', $rowNumber);
        }

        $hiredAt = trim($row['fecha_ingreso'] ?? '');
        if ($hiredAt === '') {
            $errors[] = sprintf('Fila %d: Fecha de ingreso es obligatoria', $rowNumber);
        } elseif (!preg_match(self::DATE_PATTERN, $hiredAt)) {
            $errors[] = sprintf('Fila %d: Fecha de ingreso inválida (use YYYY-MM-DD)', $rowNumber);
        }

        $profile = trim($row['perfil'] ?? '');
        if ($profile !== '' && !in_array($profile, self::VALID_PROFILES, true)) {
            $errors[] = sprintf('Fila %d: Perfil inválido (%s). Válidos: %s', $rowNumber, $profile, implode(', ', self::VALID_PROFILES));
        }

        return $errors;
    }
}
