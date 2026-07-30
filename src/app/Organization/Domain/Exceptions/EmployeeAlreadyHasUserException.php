<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class EmployeeAlreadyHasUserException extends DomainException
{
    public static function withId(string $employeeId): self
    {
        return new self(sprintf(
            'El empleado "%s" ya tiene un usuario vinculado.',
            $employeeId,
        ));
    }
}
