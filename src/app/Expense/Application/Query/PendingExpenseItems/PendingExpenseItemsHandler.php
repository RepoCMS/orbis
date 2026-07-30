<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\PendingExpenseItems;

use App\Expense\Application\Query\Response\ExpenseItemResponse;
use App\Expense\Domain\Enums\ExpenseItemStatus;
use App\Expense\Domain\Enums\ExpenseStatus;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;

final readonly class PendingExpenseItemsHandler
{
    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepo,
    ) {}

    /**
     * @return ExpenseItemResponse[]
     */
    public function __invoke(PendingExpenseItemsQuery $query): array
    {
        $submittedExpenses = $this->expenseRepo->findByStatus(ExpenseStatus::SUBMITTED);
        $pendingItems = [];

        foreach ($submittedExpenses as $expense) {
            if ($query->employeeId !== null && $expense->employeeId() !== $query->employeeId) {
                continue;
            }

            foreach ($expense->items() as $item) {
                if ($item->status() === ExpenseItemStatus::PENDING) {
                    $pendingItems[] = ExpenseItemResponse::fromEntity($item);
                }
            }
        }

        return $pendingItems;
    }
}
