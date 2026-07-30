<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Import;

use App\Shared\Application\Import\RowValidatorInterface;

final readonly class TerminalModelRowValidator implements RowValidatorInterface
{
    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $name = trim($row['name'] ?? '');
        if ($name === '') {
            $errors[] = sprintf('Fila %d: Nombre del modelo es obligatorio', $rowNumber);
        } elseif (strlen($name) > 200) {
            $errors[] = sprintf('Fila %d: Nombre no puede exceder 200 caracteres', $rowNumber);
        }

        return $errors;
    }
}
