<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Import;

use App\Shared\Application\Import\RowValidatorInterface;

final readonly class ModelPartRowValidator implements RowValidatorInterface
{
    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $modelo = trim($row['modelo'] ?? '');
        if ($modelo === '') {
            $errors[] = sprintf('Fila %d: Modelo es obligatorio', $rowNumber);
        } elseif (strlen($modelo) > 200) {
            $errors[] = sprintf('Fila %d: Modelo no puede exceder 200 caracteres', $rowNumber);
        }

        $nombreParte = trim($row['nombre_parte'] ?? '');
        if ($nombreParte === '') {
            $errors[] = sprintf('Fila %d: Nombre de parte es obligatorio', $rowNumber);
        } elseif (strlen($nombreParte) > 255) {
            $errors[] = sprintf('Fila %d: Nombre de parte no puede exceder 255 caracteres', $rowNumber);
        }

        $numeroParte = trim($row['numero_parte'] ?? '');
        if ($numeroParte === '') {
            $errors[] = sprintf('Fila %d: Número de parte es obligatorio', $rowNumber);
        } elseif (strlen($numeroParte) > 100) {
            $errors[] = sprintf('Fila %d: Número de parte no puede exceder 100 caracteres', $rowNumber);
        }

        $nivel = trim($row['nivel'] ?? '');
        if ($nivel !== '') {
            if (!ctype_digit($nivel)) {
                $errors[] = sprintf('Fila %d: Nivel debe ser un número entero', $rowNumber);
            }
        }

        $padre = trim($row['padre'] ?? '');
        if ($padre !== '' && strlen($padre) > 100) {
            $errors[] = sprintf('Fila %d: Padre no puede exceder 100 caracteres', $rowNumber);
        }

        return $errors;
    }
}
