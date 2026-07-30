<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\DeleteOperatingExpense\DeleteOperatingExpenseCommand;
use App\Expense\Application\Command\DeleteOperatingExpense\DeleteOperatingExpenseHandler;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DeleteOperatingExpenseHandlerTest extends TestCase
{
    private const EXPENSE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testDeletesExpenseSuccessfully(): void
    {
        $expense = OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: 'EMP001',
            reason: 'Gastos',
            startDate: new DateTimeImmutable('2026-01-01'),
            endDate: new DateTimeImmutable('2026-01-15'),
        );

        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn($expense);
        $repo->expects($this->once())->method('save');

        $handler = new DeleteOperatingExpenseHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );
        ($handler)(new DeleteOperatingExpenseCommand(expenseId: self::EXPENSE_ID));

        self::assertTrue($expense->isDeleted());
    }

    public function testThrowsWhenExpenseNotFound(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new DeleteOperatingExpenseHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        ($handler)(new DeleteOperatingExpenseCommand(expenseId: self::EXPENSE_ID));
    }
}
