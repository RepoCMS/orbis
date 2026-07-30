<?php

declare(strict_types=1);

namespace App\Organization\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class EmployeeDuplicateEmailException extends ApplicationException
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf(
            'El correo "%s" ya está registrado por otro empleado.',
            $email,
        ));
    }
}
