<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Query;

use App\Expense\Application\Query\ListOperatingExpenses\ListOperatingExpensesHandler;
use App\Expense\Application\Query\ListOperatingExpenses\ListOperatingExpensesQuery;
use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListOperatingExpensesHandlerTest extends TestCase
{
    private static function ulid(): string
    {
        $chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $ulid = '';
        for ($i = 0; $i < 26; $i++) {
            $ulid .= $chars[random_int(0, 31)];
        }

        return $ulid;
    }

    public function testReturnsAllWhenNoFilters(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findAll')->willReturn([
            $this->createExpense('EMP001'),
            $this->createExpense('EMP002'),
        ]);

        $handler = new ListOperatingExpensesHandler($repo);
        $result = ($handler)(new ListOperatingExpensesQuery());

        self::assertCount(2, $result);
    }

    public function testFiltersByEmployeeId(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findAll')->willReturn([
            $this->createExpense('EMP001'),
            $this->createExpense('EMP002'),
        ]);

        $handler = new ListOperatingExpensesHandler($repo);
        $result = ($handler)(new ListOperatingExpensesQuery(employeeId: 'EMP001'));

        self::assertCount(1, $result);
    }

    public function testFiltersByStatus(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $e1 = $this->createExpense('EMP001');
        $item = ExpenseItem::create(
            id: new ExpenseItemIdValueObject(self::ulid()),
            type: ExpenseItemType::TAXI,
            amount: 100.0,
        );
        $e1->addItem($item);
        $e1->submit();
        $e2 = $this->createExpense('EMP002');

        $repo->method('findAll')->willReturn([$e1, $e2]);

        $handler = new ListOperatingExpensesHandler($repo);
        $result = ($handler)(new ListOperatingExpensesQuery(status: 'submitted'));

        self::assertCount(1, $result);
    }

    public function testFiltersByDateRange(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $expense = $this->createExpense('EMP001', dateFrom: '2026-01-15', dateTo: '2026-01-20');
        $repo->method('findAll')->willReturn([$expense]);

        $handler = new ListOperatingExpensesHandler($repo);
        $result = ($handler)(new ListOperatingExpensesQuery(
            dateFrom: '2026-01-01',
            dateTo: '2026-01-31',
        ));

        self::assertCount(1, $result);
    }

    public function testFiltersByTicketId(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findAll')->willReturn([
            $this->createExpense('EMP001', ticketIds: ['TKT001']),
            $this->createExpense('EMP002', ticketIds: []),
        ]);

        $handler = new ListOperatingExpensesHandler($repo);
        $result = ($handler)(new ListOperatingExpensesQuery(ticketId: 'TKT001'));

        self::assertCount(1, $result);
    }

    public function testReturnsEmptyWhenNoMatch(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$this->createExpense('EMP001')]);

        $handler = new ListOperatingExpensesHandler($repo);
        $result = ($handler)(new ListOperatingExpensesQuery(employeeId: 'NONEXISTENT'));

        self::assertCount(0, $result);
    }

    private function createExpense(
        string $employeeId,
        array $ticketIds = [],
        string $dateFrom = '2026-01-15',
        string $dateTo = '2026-01-20',
    ): OperatingExpense {
        return OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::ulid()),
            sequence: 1,
            employeeId: $employeeId,
            reason: 'Gastos de viaje',
            startDate: new \DateTimeImmutable($dateFrom),
            endDate: new \DateTimeImmutable($dateTo),
            ticketIds: $ticketIds,
        );
    }
}
