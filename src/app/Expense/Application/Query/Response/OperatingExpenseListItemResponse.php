<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\Response;

use App\Expense\Domain\Entities\OperatingExpense;

/**
 * Lightweight DTO for list endpoints (no items array).
 */
final class OperatingExpenseListItemResponse
{
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
    ) {
    }

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
            $negativeBalance = $totalExpenses;
        }

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
        );
    }
}
