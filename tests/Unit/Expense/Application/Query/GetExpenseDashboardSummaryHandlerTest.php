<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Query;

use App\Expense\Application\Query\GetExpenseDashboardSummary\GetExpenseDashboardSummaryHandler;
use App\Expense\Application\Query\GetExpenseDashboardSummary\GetExpenseDashboardSummaryQuery;
use App\Expense\Application\Query\Response\DashboardSummaryResponse;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class GetExpenseDashboardSummaryHandlerTest extends TestCase
{
    public function testReturnsDashboardSummary(): void
    {
        $dbal = $this->createMock(Connection::class);

        // fetchOne returns for each call: pendingApproval, totalOutstandingAdvances, overdueAdvances,
        // monthlyAdvanceTotal, monthlyApprovedExpenseTotal, totalExpenses, totalAdvances, pendingSettlementAdvances
        $dbal->method('fetchOne')->willReturnOnConsecutiveCalls(
            '2',   // pending approval
            '500', // outstanding advances
            '1',   // overdue advances
            '300', // monthly advance total
            '200', // monthly approved expense total
            '5',   // total expenses
            '3',   // total advances
            '0',   // pending settlement advances
        );

        $dbal->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            // monthlyRows: employee_id + total
            [['employee_id' => 'EMP001', 'total' => '500'], ['employee_id' => 'EMP002', 'total' => '300']],
            // recentExpenses
            [['id' => 'ID001', 'employee_id' => 'EMP001', 'status' => 'submitted', 'start_date' => '2026-06-01 00:00:00', 'total' => '500']],
            // expenseByStatus
            [['status' => 'submitted', 'count' => '2'], ['status' => 'draft', 'count' => '1']],
            // expenseByType
            [['type' => 'taxi', 'count' => '2', 'total' => '400'], ['type' => 'hotel', 'count' => '1', 'total' => '300']],
            // advancesByTechnician
            [['employee_id' => 'EMP001', 'count' => '2', 'total' => '400']],
        );

        $handler = new GetExpenseDashboardSummaryHandler($dbal);
        $result = ($handler)(new GetExpenseDashboardSummaryQuery());

        self::assertInstanceOf(DashboardSummaryResponse::class, $result);
        self::assertSame(2, $result->pendingApproval);
        self::assertSame(500.0, $result->totalOutstandingAdvances);
        self::assertSame(1, $result->overdueAdvances);
        self::assertSame(800.0, $result->monthlyExpenseTotal);
        self::assertSame(300.0, $result->monthlyAdvanceTotal);
        self::assertSame(200.0, $result->monthlyApprovedExpenseTotal);
        self::assertCount(2, $result->expenseByType);
        self::assertSame('taxi', $result->expenseByType[0]['type']);
        self::assertCount(1, $result->advancesByTechnician);
    }

    public function testHandlesEmptyData(): void
    {
        $dbal = $this->createMock(Connection::class);

        $dbal->method('fetchOne')->willReturnOnConsecutiveCalls(
            '0',
            '0',
            '0',
            '0',
            '0',
            '0',
            '0',
            '0',
        );

        $dbal->method('fetchAllAssociative')->willReturn([]);

        $handler = new GetExpenseDashboardSummaryHandler($dbal);
        $result = ($handler)(new GetExpenseDashboardSummaryQuery());

        self::assertSame(0, $result->pendingApproval);
        self::assertSame(0.0, $result->totalOutstandingAdvances);
        self::assertSame(0, $result->totalExpenses);
        self::assertSame(0, $result->totalAdvances);
        self::assertSame(0, $result->overdueAdvances);
        self::assertSame([], $result->recentExpenses);
        self::assertSame([], $result->expenseByType);
        self::assertSame([], $result->advancesByTechnician);
        self::assertSame(0, $result->pendingSettlementAdvances);
    }

    public function testCountsOverdueAdvances(): void
    {
        $dbal = $this->createMock(Connection::class);

        $dbal->method('fetchOne')->willReturnOnConsecutiveCalls(
            '0',   // pending approval
            '300', // outstanding advances
            '1',   // overdue advances
            '300', // monthly advance total
            '50',  // monthly approved expense total
            '0',   // total expenses
            '2',   // total advances
            '0',   // pending settlement advances
        );

        $dbal->method('fetchAllAssociative')->willReturn([]);

        $handler = new GetExpenseDashboardSummaryHandler($dbal);
        $result = ($handler)(new GetExpenseDashboardSummaryQuery());

        self::assertSame(1, $result->overdueAdvances);
        self::assertSame(300.0, $result->totalOutstandingAdvances);
    }
}
