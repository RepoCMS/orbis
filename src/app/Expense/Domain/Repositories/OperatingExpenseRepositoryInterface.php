<?php

declare(strict_types=1);

namespace App\Expense\Domain\Repositories;

use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Enums\ExpenseStatus;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;

interface OperatingExpenseRepositoryInterface
{
    public function findById(OperatingExpenseIdValueObject $id): ?OperatingExpense;

    /** @return OperatingExpense[] */
    public function findByEmployeeId(string $employeeId): array;

    /** @return OperatingExpense[] */
    public function findByStatus(ExpenseStatus $status): array;

    public function findByCashAdvanceId(string $cashAdvanceId): ?OperatingExpense;

    /**
     * @param array{filters?: array, page?: int, limit?: int} $filters
     * @return OperatingExpense[]
     */
    public function findAll(array $filters = []): array;

    /**
     * Retorna el siguiente número de secuencia para un empleado en un año dado.
     */
    public function nextSequence(string $employeeId): int;

    public function save(OperatingExpense $expense): void;

    public function delete(OperatingExpenseIdValueObject $id): void;
}
