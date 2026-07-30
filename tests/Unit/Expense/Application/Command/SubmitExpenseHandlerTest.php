<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\SubmitExpense\SubmitExpenseCommand;
use App\Expense\Application\Command\SubmitExpense\SubmitExpenseHandler;
use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Enums\ExpenseStatus;
use App\Expense\Domain\Events\ExpenseSubmitted;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SubmitExpenseHandlerTest extends TestCase
{
    private const EXPENSE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ITEM_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testSubmitsExpenseSuccessfully(): void
    {
        $expense = OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: 'EMP001',
            reason: 'Gastos',
            startDate: new DateTimeImmutable('2026-01-01'),
            endDate: new DateTimeImmutable('2026-01-15'),
        );
        $item = ExpenseItem::create(
            id: new ExpenseItemIdValueObject(self::ITEM_ID),
            type: ExpenseItemType::UBER_EMPRESARIAL,
            amount: 50.00,
        );
        $item->uploadEvidence('/evidence/receipt.jpg');
        $expense->addItem($item);
        $expense->pullDomainEvents();

        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn($expense);
        $repo->expects($this->once())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(ExpenseSubmitted::class));

        $handler = new SubmitExpenseHandler($repo, $publisher);
        ($handler)(new SubmitExpenseCommand(expenseId: self::EXPENSE_ID));

        self::assertSame(ExpenseStatus::SUBMITTED, $expense->status());
    }

    public function testThrowsWhenExpenseNotFound(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new SubmitExpenseHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        ($handler)(new SubmitExpenseCommand(expenseId: self::EXPENSE_ID));
    }
}
