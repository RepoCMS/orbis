<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Query;

use App\Expense\Application\Query\PendingExpenseItems\PendingExpenseItemsHandler;
use App\Expense\Application\Query\PendingExpenseItems\PendingExpenseItemsQuery;
use App\Expense\Application\Query\Response\ExpenseItemResponse;
use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Enums\ExpenseStatus;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use PHPUnit\Framework\TestCase;

final class PendingExpenseItemsHandlerTest extends TestCase
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
    public function testReturnsPendingItemsFromSubmittedExpenses(): void
    {
        $expense = $this->createSubmittedExpenseWithItems(
            employeeId: 'EMP001',
            pendingAmount: 1,
            approvedAmount: 1,
        );

        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findByStatus')->with(ExpenseStatus::SUBMITTED)->willReturn([$expense]);

        $handler = new PendingExpenseItemsHandler($repo);
        $result = ($handler)(new PendingExpenseItemsQuery());

        self::assertCount(1, $result);
        self::assertInstanceOf(ExpenseItemResponse::class, $result[0]);
    }

    public function testFiltersByEmployeeId(): void
    {
        $e1 = $this->createSubmittedExpenseWithItems('EMP001', pendingAmount: 1);
        $e2 = $this->createSubmittedExpenseWithItems('EMP002', pendingAmount: 1);

        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findByStatus')->willReturn([$e1, $e2]);

        $handler = new PendingExpenseItemsHandler($repo);
        $result = ($handler)(new PendingExpenseItemsQuery(employeeId: 'EMP001'));

        self::assertCount(1, $result);
    }

    public function testReturnsEmptyWhenNoPendingItems(): void
    {
        $expense = $this->createExpenseWithStatus('EMP001', ExpenseStatus::DRAFT);

        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findByStatus')->willReturn([]);

        $handler = new PendingExpenseItemsHandler($repo);
        $result = ($handler)(new PendingExpenseItemsQuery());

        self::assertCount(0, $result);
    }

    private function createSubmittedExpenseWithItems(
        string $employeeId,
        int $pendingAmount = 0,
        int $approvedAmount = 0,
    ): OperatingExpense {
        $id = new OperatingExpenseIdValueObject(self::ulid());
        $expense = OperatingExpense::create(
            id: $id,
            sequence: 1,
            employeeId: $employeeId,
            reason: 'Gastos de viaje',
            startDate: new \DateTimeImmutable('2026-01-15'),
            endDate: new \DateTimeImmutable('2026-01-20'),
        );

        for ($i = 0; $i < $pendingAmount; $i++) {
            $expense->addItem(ExpenseItem::create(
                id: new ExpenseItemIdValueObject(self::ulid()),
                type: ExpenseItemType::TAXI,
                amount: 50.0,
            ));
        }

        for ($i = 0; $i < $approvedAmount; $i++) {
            $item = ExpenseItem::create(
                id: new ExpenseItemIdValueObject(self::ulid()),
                type: ExpenseItemType::TAXI,
                amount: 100.0,
            );
            $item->approve('REVIEWER001');
            $expense->addItem($item);
        }

        $expense->submit();

        return $expense;
    }

    private function createExpenseWithStatus(string $employeeId, ExpenseStatus $status): OperatingExpense
    {
        $id = new OperatingExpenseIdValueObject(self::ulid());

        return OperatingExpense::create(
            id: $id,
            sequence: 1,
            employeeId: $employeeId,
            reason: 'Test',
            startDate: new \DateTimeImmutable('2026-01-15'),
            endDate: new \DateTimeImmutable('2026-01-20'),
        );
    }
}
