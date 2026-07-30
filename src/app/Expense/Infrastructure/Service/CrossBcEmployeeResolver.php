<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Service;

use App\Expense\Application\Exception\EmployeeNotActiveException;
use App\Expense\Application\Exception\EmployeeNotFoundException;
use App\Expense\Application\Service\EmployeeResolverInterface;
use Doctrine\DBAL\Connection;

/**
 * Resuelve un Employee desde Organization BC vía DBAL directo.
 * Verifica existencia y estado activo (status = 'active' + terminatedAt IS NULL).
 */
final readonly class CrossBcEmployeeResolver implements EmployeeResolverInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findActive(string $employeeId): void
    {
        $employee = $this->connection->fetchAssociative(
            'SELECT id, status, terminated_at FROM employees WHERE id = ? AND deleted_at IS NULL',
            [$employeeId],
        );

        if ($employee === false) {
            throw new EmployeeNotFoundException($employeeId);
        }

        if ($employee['status'] !== 'active' || $employee['terminated_at'] !== null) {
            throw new EmployeeNotActiveException($employeeId);
        }
    }
}
