<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Import;

use App\Shared\Application\Import\RowValidatorInterface;

final readonly class UserRowValidator implements RowValidatorInterface
{
    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $username = trim($row['username'] ?? '');
        if ($username === '') {
            $errors[] = sprintf('Fila %d: Username es obligatorio', $rowNumber);
        } elseif (strlen($username) < 5 || strlen($username) > 15) {
            $errors[] = sprintf('Fila %d: Username debe tener entre 5 y 15 caracteres', $rowNumber);
        } elseif (!preg_match('/^[a-z0-9._-]+$/', $username)) {
            $errors[] = sprintf('Fila %d: Username solo permite minúsculas, números, puntos, guiones y guiones bajos', $rowNumber);
        }

        $roleSlug = trim($row['role_slug'] ?? '');
        if ($roleSlug === '') {
            $errors[] = sprintf('Fila %d: Role slug es obligatorio', $rowNumber);
        } elseif (strlen($roleSlug) > 60) {
            $errors[] = sprintf('Fila %d: Role slug no puede exceder 60 caracteres', $rowNumber);
        }

        $password = trim($row['raw_password'] ?? '');
        if ($password !== '' && strlen($password) < 8) {
            $errors[] = sprintf('Fila %d: Password debe tener al menos 8 caracteres', $rowNumber);
        }

        return $errors;
    }
}
