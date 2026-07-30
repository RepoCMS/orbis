<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class EmployeeOnVacationException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            sprintf('El empleado con ID "%s" se encuentra de vacaciones.', $id)
        );
    }
}
