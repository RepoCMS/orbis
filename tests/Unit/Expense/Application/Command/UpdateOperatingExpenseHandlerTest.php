<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\UpdateOperatingExpense\UpdateOperatingExpenseCommand;
use App\Expense\Application\Command\UpdateOperatingExpense\UpdateOperatingExpenseHandler;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UpdateOperatingExpenseHandlerTest extends TestCase
{
    private const EXPENSE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testUpdatesExpenseSuccessfully(): void
    {
        $expense = OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: 'EMP001',
            reason: 'Gastos originales',
            startDate: new DateTimeImmutable('2026-01-01'),
            endDate: new DateTimeImmutable('2026-01-15'),
        );

        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn($expense);
        $repo->expects($this->once())->method('save');

        $handler = new UpdateOperatingExpenseHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        ($handler)(new UpdateOperatingExpenseCommand(
            expenseId: self::EXPENSE_ID,
            reason: 'Gastos actualizados',
            startDate: '2026-02-01',
            endDate: '2026-02-15',
        ));

        self::assertSame('Gastos actualizados', $expense->reason());
    }

    public function testThrowsWhenExpenseNotFound(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateOperatingExpenseHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        ($handler)(new UpdateOperatingExpenseCommand(
            expenseId: self::EXPENSE_ID,
            reason: 'Nuevo motivo',
            startDate: '2026-02-01',
            endDate: '2026-02-15',
        ));
    }
}
