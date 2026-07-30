<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\UploadEvidence\UploadEvidenceCommand;
use App\Expense\Application\Command\UploadEvidence\UploadEvidenceHandler;
use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UploadEvidenceHandlerTest extends TestCase
{
    private const EXPENSE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ITEM_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testUploadsEvidenceSuccessfully(): void
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
        $expense->addItem($item);

        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn($expense);
        $repo->expects($this->once())->method('save');

        $handler = new UploadEvidenceHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        ($handler)(new UploadEvidenceCommand(
            expenseId: self::EXPENSE_ID,
            itemId: self::ITEM_ID,
            filePath: '/uploads/evidence/receipt.pdf',
        ));

        self::assertSame('/uploads/evidence/receipt.pdf', $item->evidencePath());
    }

    public function testThrowsWhenExpenseNotFound(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UploadEvidenceHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        ($handler)(new UploadEvidenceCommand(
            expenseId: self::EXPENSE_ID,
            itemId: self::ITEM_ID,
            filePath: '/uploads/evidence/receipt.pdf',
        ));
    }
}
