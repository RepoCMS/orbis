<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\AddExpenseItem\AddExpenseItemCommand;
use App\Expense\Application\Command\AddExpenseItem\AddExpenseItemHandler;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AddExpenseItemHandlerTest extends TestCase
{
    private const EXPENSE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ITEM_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testAddsItemSuccessfully(): void
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

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::ITEM_ID);

        $handler = new AddExpenseItemHandler(
            $repo,
            $idGenerator,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $result = ($handler)(new AddExpenseItemCommand(
            expenseId: self::EXPENSE_ID,
            type: 'taxi',
            amount: 25.50,
        ));

        self::assertSame(self::ITEM_ID, $result);
        self::assertCount(1, $expense->items());
    }

    public function testThrowsWhenExpenseNotFound(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new AddExpenseItemHandler(
            $repo,
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(\InvalidArgumentException::class);

        ($handler)(new AddExpenseItemCommand(
            expenseId: self::EXPENSE_ID,
            type: 'taxi',
            amount: 25.50,
        ));
    }
}
