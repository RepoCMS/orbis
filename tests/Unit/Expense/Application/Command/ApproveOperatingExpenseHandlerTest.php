<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\ApproveOperatingExpense\ApproveOperatingExpenseCommand;
use App\Expense\Application\Command\ApproveOperatingExpense\ApproveOperatingExpenseHandler;
use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Enums\ExpenseStatus;
use App\Expense\Domain\Events\ExpenseApproved;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ApproveOperatingExpenseHandlerTest extends TestCase
{
    private const EXPENSE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ITEM_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    private function createSubmittedExpense(): OperatingExpense
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
        $expense->submit();
        $expense->pullDomainEvents();

        foreach ($expense->items() as $item) {
            $item->approve('REVIEWER001');
        }

        return $expense;
    }

    public function testApprovesExpenseSuccessfully(): void
    {
        $expense = $this->createSubmittedExpense();

        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn($expense);
        $repo->expects($this->once())->method('save');

        $userResolver = $this->createMock(UserResolverInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(ExpenseApproved::class));

        $handler = new ApproveOperatingExpenseHandler($repo, $userResolver, $publisher);
        ($handler)(new ApproveOperatingExpenseCommand(
            expenseId: self::EXPENSE_ID,
            approvedBy: 'APR001',
        ));

        self::assertSame(ExpenseStatus::APPROVED, $expense->status());
    }

    public function testThrowsWhenExpenseNotFound(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new ApproveOperatingExpenseHandler(
            $repo,
            $this->createMock(UserResolverInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        ($handler)(new ApproveOperatingExpenseCommand(
            expenseId: self::EXPENSE_ID,
            approvedBy: 'APR001',
        ));
    }
}
