<?php

declare(strict_types=1);

namespace App\Expense\Domain\Repositories;

use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;

interface ExpenseItemRepositoryInterface
{
    public function findById(ExpenseItemIdValueObject $id): ?ExpenseItem;

    /** @return ExpenseItem[] */
    public function findByExpenseId(string $expenseId): array;

    /** @return ExpenseItem[] */
    public function findPendingByExpenseId(string $expenseId): array;

    public function save(ExpenseItem $item): void;
}
