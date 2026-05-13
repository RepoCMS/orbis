<?php

declare(strict_types=1);

namespace App\Organization\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class EmployeeNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Empleado con ID "%s" no encontrado.', $id));
    }
}
