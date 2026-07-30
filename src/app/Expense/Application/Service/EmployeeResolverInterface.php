<?php

declare(strict_types=1);

namespace App\Expense\Application\Service;

use App\Expense\Application\Exception\EmployeeNotActiveException;
use App\Expense\Application\Exception\EmployeeNotFoundException;

interface EmployeeResolverInterface
{
    /**
     * Validates that an employee exists in Organization BC and is active.
     *
     * @throws EmployeeNotFoundException
     * @throws EmployeeNotActiveException
     */
    public function findActive(string $employeeId): void;
}
