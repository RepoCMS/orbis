<?php

declare(strict_types=1);

namespace App\Expense\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class EmployeeNotFoundException extends ApplicationException
{
    public function __construct(string $employeeId)
    {
        parent::__construct(
            sprintf('Empleado no encontrado: %s', $employeeId),
        );
    }
}
