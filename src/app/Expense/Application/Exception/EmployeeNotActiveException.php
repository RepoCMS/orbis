<?php

declare(strict_types=1);

namespace App\Expense\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class EmployeeNotActiveException extends ApplicationException
{
    public function __construct(string $employeeId)
    {
        parent::__construct(
            sprintf('El empleado %s no está activo', $employeeId),
        );
    }
}
