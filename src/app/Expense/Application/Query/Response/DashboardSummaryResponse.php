<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\Response;

final class DashboardSummaryResponse
{
    /**
     * @param array $monthlyTotalByTechnician  [{employeeId, total}] — expenses this month per employee
     * @param array $recentExpenses            Last 5 expenses: [{id, employeeId, status, total, startDate}]
     * @param array $expenseByStatus           [{status, count}]
     * @param array $expenseByType             [{type, total, count}] — items grouped by ExpenseItemType
     * @param array $advancesByTechnician      [{employeeId, total, count}] — outstanding advances per employee
     * @param int   $pendingSettlementAdvances  Advances with settlement_direction set (pendiente de liquidación formal)
     * @param float $monthlyApprovedExpenseTotal  Sum of approved expense items in current month
     */
    public function __construct(
        public readonly int $pendingApproval,
        public readonly float $totalOutstandingAdvances,
        public readonly int $overdueAdvances,
        public readonly array $monthlyTotalByTechnician,
        public readonly float $monthlyExpenseTotal = 0.0,
        public readonly float $monthlyAdvanceTotal = 0.0,
        public readonly int $totalExpenses = 0,
        public readonly int $totalAdvances = 0,
        public readonly array $recentExpenses = [],
        public readonly array $expenseByStatus = [],
        public readonly array $expenseByType = [],
        public readonly array $advancesByTechnician = [],
        public readonly int $pendingSettlementAdvances = 0,
        public readonly float $monthlyApprovedExpenseTotal = 0.0,
    ) {}
}
