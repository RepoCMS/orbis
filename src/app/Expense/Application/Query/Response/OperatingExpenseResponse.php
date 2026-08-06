<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\Response;

use App\Expense\Domain\Entities\OperatingExpense;

final class OperatingExpenseResponse
{
    /**
     * @param ExpenseItemResponse[] $items
     */
    public function __construct(
        public readonly string $id,
        public readonly int $sequence,
        public readonly string $employeeId,
        public readonly string $reason,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $cashAdvanceId,
        public readonly ?float $cashAdvanceAmount,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly float $totalExpenses,
        public readonly ?float $positiveBalance,
        public readonly ?float $negativeBalance,
        public readonly ?string $submittedAt,
        public readonly ?string $approvedBy,
        public readonly ?string $approvedAt,
        public readonly array $items = [],
    ) {
    }

    /**
     * @param OperatingExpense $expense
     * @param float|null $cashAdvanceAmount The actual cash advance amount (resolved from CashAdvance BC)
     */
    public static function fromEntity(OperatingExpense $expense, ?float $cashAdvanceAmount = null): self
    {
        $totalExpenses = $expense->totalExpenses();
        $caAmount = $cashAdvanceAmount ?? 0.0;

        $positiveBalance = null;
        $negativeBalance = null;

        if ($caAmount > 0) {
            $balance = $caAmount - $totalExpenses;
            if ($balance >= 0) {
                $positiveBalance = $balance;
            } else {
                $negativeBalance = abs($balance);
            }
        } elseif ($expense->cashAdvanceId() === null && $totalExpenses > 0) {
            // No cash advance — full reimbursement owed to technician
            $negativeBalance = $totalExpenses;
        }

        $items = array_map(
            fn ($item) => ExpenseItemResponse::fromEntity($item),
            $expense->items()
        );

        return new self(
            id: (string) $expense->id(),
            sequence: $expense->sequence(),
            employeeId: $expense->employeeId(),
            reason: $expense->reason(),
            startDate: $expense->startDate()->format('Y-m-d'),
            endDate: $expense->endDate()->format('Y-m-d'),
            cashAdvanceId: $expense->cashAdvanceId(),
            cashAdvanceAmount: $cashAdvanceAmount,
            status: $expense->status()->value,
            statusLabel: $expense->status()->label(),
            totalExpenses: $totalExpenses,
            positiveBalance: $positiveBalance,
            negativeBalance: $negativeBalance,
            submittedAt: $expense->submittedAt()?->format('Y-m-d\TH:i:s\Z'),
            approvedBy: $expense->approvedBy(),
            approvedAt: $expense->approvedAt()?->format('Y-m-d\TH:i:s\Z'),
            items: $items,
        );
    }
}
