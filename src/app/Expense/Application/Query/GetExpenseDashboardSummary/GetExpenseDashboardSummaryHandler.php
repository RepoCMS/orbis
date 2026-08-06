<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\GetExpenseDashboardSummary;

use App\Expense\Application\Query\Response\DashboardSummaryResponse;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final readonly class GetExpenseDashboardSummaryHandler
{
    private const TABLE_EXPENSES = 'operating_expenses';
    private const TABLE_ITEMS = 'expense_items';
    private const TABLE_ADVANCES = 'cash_advances';

    public function __construct(
        private Connection $dbal,
    ) {
    }

    public function __invoke(GetExpenseDashboardSummaryQuery $query): DashboardSummaryResponse
    {
        $now = new DateTimeImmutable();
        $startOfMonth = new DateTimeImmutable('first day of this month 00:00:00');
        $endOfMonth = new DateTimeImmutable('last day of this month 23:59:59');
        $startOfMonthStr = $startOfMonth->format('Y-m-d H:i:s');
        $endOfMonthStr = $endOfMonth->format('Y-m-d H:i:s');

        // 1. Pending approval: count of SUBMITTED operating expenses
        $pendingApproval = (int) $this->dbal->fetchOne(
            "SELECT COUNT(*) FROM {self::TABLE_EXPENSES} WHERE status = 'submitted' AND deleted_at IS NULL",
        );

        // 2. Outstanding advances (sum of amounts)
        $totalOutstandingAdvances = (float) $this->dbal->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) FROM {self::TABLE_ADVANCES} WHERE status = 'outstanding' AND deleted_at IS NULL",
        );

        // 3. Overdue advances (> 3 days)
        $overdueAdvances = (int) $this->dbal->fetchOne(
            "SELECT COUNT(*) FROM {self::TABLE_ADVANCES} WHERE status = 'outstanding' AND deleted_at IS NULL AND given_at < DATE_SUB(NOW(), INTERVAL 3 DAY)",
        );

        // 4. Monthly totals by technician (from operating_expenses, non-draft, overlapping this month)
        $monthlyRows = $this->dbal->fetchAllAssociative(
            "SELECT employee_id, SUM(total) as total
             FROM (
                 SELECT oe.employee_id, COALESCE(ei.month_total, 0) as total
                 FROM {self::TABLE_EXPENSES} oe
                 LEFT JOIN (
                     SELECT expense_id, SUM(amount) as month_total
                     FROM {self::TABLE_ITEMS}
                     WHERE deleted_at IS NULL
                     GROUP BY expense_id
                 ) ei ON ei.expense_id = oe.id
                 WHERE oe.status != 'draft'
                   AND oe.deleted_at IS NULL
                   AND oe.start_date <= :endMonth
                   AND oe.end_date >= :startMonth
             ) sub
             GROUP BY employee_id
             ORDER BY total DESC",
            ['startMonth' => $startOfMonthStr, 'endMonth' => $endOfMonthStr],
        );

        $monthlyTotalByTechnician = array_map(
            fn (array $row) => ['employeeId' => $row['employee_id'], 'total' => (float) $row['total']],
            $monthlyRows,
        );

        $monthlyExpenseTotal = array_sum(array_column($monthlyTotalByTechnician, 'total'));

        // 5. Monthly advance total
        $monthlyAdvanceTotal = (float) $this->dbal->fetchOne(
            'SELECT COALESCE(SUM(amount), 0) FROM {self::TABLE_ADVANCES}
             WHERE deleted_at IS NULL
               AND given_at >= :startMonth
               AND given_at <= :endMonth',
            ['startMonth' => $startOfMonthStr, 'endMonth' => $endOfMonthStr],
        );

        // 6. Monthly approved expense items total (approved items in current month)
        $monthlyApprovedExpenseTotal = (float) $this->dbal->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) FROM {self::TABLE_ITEMS}
             WHERE status = 'approved'
               AND deleted_at IS NULL
               AND created_at >= :startMonth
               AND created_at <= :endMonth",
            ['startMonth' => $startOfMonthStr, 'endMonth' => $endOfMonthStr],
        );

        // 7. Totals
        $totalExpenses = (int) $this->dbal->fetchOne(
            "SELECT COUNT(*) FROM {self::TABLE_EXPENSES} WHERE status != 'draft' AND deleted_at IS NULL",
        );
        $totalAdvances = (int) $this->dbal->fetchOne(
            'SELECT COUNT(*) FROM {self::TABLE_ADVANCES} WHERE deleted_at IS NULL',
        );

        // 7. Recent expenses (last 5 non-draft)
        $recentRows = $this->dbal->fetchAllAssociative(
            "SELECT oe.id, oe.employee_id, oe.status, oe.start_date,
                    COALESCE(SUM(ei.amount), 0) as total
             FROM {self::TABLE_EXPENSES} oe
             LEFT JOIN {self::TABLE_ITEMS} ei ON ei.expense_id = oe.id AND ei.deleted_at IS NULL
             WHERE oe.status != 'draft' AND oe.deleted_at IS NULL
             GROUP BY oe.id
             ORDER BY oe.created_at DESC
             LIMIT 5",
        );

        $recentExpenses = array_map(
            fn (array $row) => [
                'id'         => $row['id'],
                'employeeId' => $row['employee_id'],
                'status'     => $row['status'],
                'total'      => (float) $row['total'],
                'startDate'  => (new DateTimeImmutable($row['start_date']))->format('Y-m-d'),
            ],
            $recentRows,
        );

        // 8. Expense by status
        $statusRows = $this->dbal->fetchAllAssociative(
            "SELECT status, COUNT(*) as count FROM {self::TABLE_EXPENSES}
             WHERE deleted_at IS NULL
             GROUP BY status
             ORDER BY FIELD(status, 'draft', 'submitted', 'approved', 'rejected')",
        );
        $expenseByStatus = array_map(
            fn (array $row) => ['status' => $row['status'], 'count' => (int) $row['count']],
            $statusRows,
        );

        // 9. Expense by type (items grouped by ExpenseItemType)
        $typeRows = $this->dbal->fetchAllAssociative(
            'SELECT ei.type, COUNT(*) as count, COALESCE(SUM(ei.amount), 0) as total
             FROM {self::TABLE_ITEMS} ei
             JOIN {self::TABLE_EXPENSES} oe ON oe.id = ei.expense_id
             WHERE ei.deleted_at IS NULL AND oe.deleted_at IS NULL
             GROUP BY ei.type
             ORDER BY total DESC',
        );
        $expenseByType = array_map(
            fn (array $row) => [
                'type'  => $row['type'],
                'count' => (int) $row['count'],
                'total' => (float) $row['total'],
            ],
            $typeRows,
        );

        // 10. Advances by technician (outstanding, grouped by employee)
        $advanceRows = $this->dbal->fetchAllAssociative(
            "SELECT employee_id, COUNT(*) as count, COALESCE(SUM(amount), 0) as total
             FROM {self::TABLE_ADVANCES}
             WHERE status = 'outstanding' AND deleted_at IS NULL
             GROUP BY employee_id
             ORDER BY total DESC",
        );
        $advancesByTechnician = array_map(
            fn (array $row) => [
                'employeeId' => $row['employee_id'],
                'count'      => (int) $row['count'],
                'total'      => (float) $row['total'],
            ],
            $advanceRows,
        );

        // 11. Pending settlement advances (outstanding + has settlement_direction)
        $pendingSettlementAdvances = (int) $this->dbal->fetchOne(
            "SELECT COUNT(*) FROM {self::TABLE_ADVANCES}
             WHERE status = 'outstanding'
               AND settlement_direction IS NOT NULL
               AND deleted_at IS NULL",
        );

        return new DashboardSummaryResponse(
            pendingApproval: $pendingApproval,
            totalOutstandingAdvances: $totalOutstandingAdvances,
            overdueAdvances: $overdueAdvances,
            monthlyTotalByTechnician: $monthlyTotalByTechnician,
            monthlyExpenseTotal: $monthlyExpenseTotal,
            monthlyAdvanceTotal: $monthlyAdvanceTotal,
            totalExpenses: $totalExpenses,
            totalAdvances: $totalAdvances,
            recentExpenses: $recentExpenses,
            expenseByStatus: $expenseByStatus,
            expenseByType: $expenseByType,
            advancesByTechnician: $advancesByTechnician,
            pendingSettlementAdvances: $pendingSettlementAdvances,
            monthlyApprovedExpenseTotal: $monthlyApprovedExpenseTotal,
        );
    }
}
