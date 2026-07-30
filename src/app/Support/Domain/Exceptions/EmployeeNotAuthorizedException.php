<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class EmployeeNotAuthorizedException extends DomainException
{
    public static function withId(string $id, string $action): self
    {
        return new self(
            sprintf('El empleado con ID "%s" no está autorizado para %s.', $id, $action)
        );
    }
}
