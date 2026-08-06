<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Domain\Entities;

use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Enums\ExpenseStatus;
use App\Expense\Domain\Events\ExpenseApproved;
use App\Expense\Domain\Events\ExpenseItemRejected;
use App\Expense\Domain\Events\ExpenseResubmitted;
use App\Expense\Domain\Events\ExpenseSubmitted;
use App\Expense\Domain\Exceptions\ExpenseHasNoItemsException;
use App\Expense\Domain\Exceptions\ExpenseHasPendingItemsException;
use App\Expense\Domain\Exceptions\ExpenseHasRejectedItemsException;
use App\Expense\Domain\Exceptions\ExpenseNotEditableException;
use App\Expense\Domain\Exceptions\MissingEvidenceException;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class OperatingExpenseTest extends TestCase
{
    private const EXPENSE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ITEM_ID_1 = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
    private const ITEM_ID_2 = '01ARZ3NDEKTSV4RRFFQ69G5FAX';

    public function testCreatesExpenseInDraftStatus(): void
    {
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-15');
        $expense = OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: 'EMP001',
            reason: 'Gastos de viaje a cliente',
            startDate: $startDate,
            endDate: $endDate,
        );

        self::assertSame(self::EXPENSE_ID, (string) $expense->id());
        self::assertSame(1, $expense->sequence());
        self::assertSame('EMP001', $expense->employeeId());
        self::assertSame('Gastos de viaje a cliente', $expense->reason());
        self::assertSame($startDate, $expense->startDate());
        self::assertSame($endDate, $expense->endDate());
        self::assertSame(ExpenseStatus::DRAFT, $expense->status());
        self::assertEmpty($expense->items());
        self::assertSame(0.0, $expense->totalExpenses());
    }

    public function testCreatesExpenseWithOptionalFields(): void
    {
        $expense = OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: 'EMP001',
            reason: 'Gastos de viaje',
            startDate: new DateTimeImmutable('2026-01-01'),
            endDate: new DateTimeImmutable('2026-01-15'),
            cashAdvanceId: 'CA-001',
            ticketIds: ['TICKET001', 'TICKET002'],
        );

        self::assertSame('CA-001', $expense->cashAdvanceId());
        self::assertSame(['TICKET001', 'TICKET002'], $expense->ticketIds());
    }

    public function testThrowsWhenStartDateAfterEndDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: 'EMP001',
            reason: 'Gastos',
            startDate: new DateTimeImmutable('2026-02-01'),
            endDate: new DateTimeImmutable('2026-01-01'),
        );
    }

    public function testThrowsWhenEmployeeIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: '',
            reason: 'Gastos',
            startDate: new DateTimeImmutable('2026-01-01'),
            endDate: new DateTimeImmutable('2026-01-15'),
        );
    }

    public function testAddsItem(): void
    {
        $expense = $this->createDraftExpense();
        $item = $this->createItem(self::ITEM_ID_1, 50.00);

        $expense->addItem($item);

        self::assertCount(1, $expense->items());
        self::assertSame(50.00, $expense->totalExpenses());
    }

    public function testThrowsWhenAddingItemToNonDraftExpense(): void
    {
        $expense = $this->createDraftExpense();
        $expense->addItem($this->createItemWithEvidence(self::ITEM_ID_1, 50.00));
        $expense->submit();

        $this->expectException(ExpenseNotEditableException::class);
        $expense->addItem($this->createItem(self::ITEM_ID_2, 30.00));
    }

    public function testSubmitChangesStatusAndRecordsEvent(): void
    {
        $expense = $this->createDraftExpense();
        $expense->addItem($this->createItemWithEvidence(self::ITEM_ID_1, 50.00));
        $expense->addItem($this->createItemWithEvidence(self::ITEM_ID_2, 30.00));

        $expense->submit();

        self::assertSame(ExpenseStatus::SUBMITTED, $expense->status());
        self::assertNotNull($expense->submittedAt());

        $events = $expense->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ExpenseSubmitted::class, $events[0]);
    }

    public function testThrowsWhenSubmittingWithoutItems(): void
    {
        $expense = $this->createDraftExpense();

        $this->expectException(ExpenseHasNoItemsException::class);
        $expense->submit();
    }

    public function testThrowsWhenSubmittingMissingEvidence(): void
    {
        $expense = $this->createDraftExpense();
        $expense->addItem(ExpenseItem::create(
            id: new ExpenseItemIdValueObject(self::ITEM_ID_1),
            type: ExpenseItemType::UBER_EMPRESARIAL,
            amount: 50.00,
        ));

        $this->expectException(MissingEvidenceException::class);
        $expense->submit();
    }

    public function testApprovesAllItems(): void
    {
        $expense = $this->createSubmittedExpenseWithReviewedItems();
        $expense->pullDomainEvents(); // descartar ExpenseSubmitted

        $expense->approve('USER001');

        self::assertSame(ExpenseStatus::APPROVED, $expense->status());
        self::assertSame('USER001', $expense->approvedBy());
        self::assertNotNull($expense->approvedAt());

        $events = $expense->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ExpenseApproved::class, $events[0]);
    }

    public function testThrowsWhenApprovingWithPendingItems(): void
    {
        $expense = $this->createSubmittedExpenseWithEvidence();
        // Items are PENDING (not reviewed)

        $this->expectException(ExpenseHasPendingItemsException::class);
        $expense->approve('USER001');
    }

    public function testThrowsWhenApprovingWithRejectedItems(): void
    {
        $expense = $this->createSubmittedExpenseWithEvidence();
        $item = $expense->items()[0];
        $item->approve('USER001');
        $item2 = $expense->items()[1];
        $item2->reject('Comprobante ilegible', 'USER001');

        $this->expectException(ExpenseHasRejectedItemsException::class);
        $expense->approve('USER002');
    }

    public function testApprovesSingleItemViaMarkItemReviewed(): void
    {
        $expense = $this->createSubmittedExpenseWithSingleItem();
        $expense->pullDomainEvents(); // descartar ExpenseSubmitted
        $expense->markItemReviewed(self::ITEM_ID_1, 'USER001');

        self::assertSame(ExpenseStatus::APPROVED, $expense->status());
        self::assertSame('USER001', $expense->approvedBy());

        $events = $expense->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ExpenseApproved::class, $events[0]);
    }

    public function testRejectingItemChangesStatusToRejected(): void
    {
        $expense = $this->createSubmittedExpenseWithSingleItem();
        $expense->pullDomainEvents(); // descartar ExpenseSubmitted
        $expense->rejectItem(self::ITEM_ID_1, 'Comprobante ilegible', 'USER001');

        self::assertSame(ExpenseStatus::REJECTED, $expense->status());

        $events = $expense->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ExpenseItemRejected::class, $events[0]);
    }

    public function testResubmitChangesRejectedToSubmitted(): void
    {
        $expense = $this->createSubmittedExpenseWithSingleItem();
        $expense->pullDomainEvents(); // descartar ExpenseSubmitted
        $expense->rejectItem(self::ITEM_ID_1, 'Comprobante ilegible', 'USER001');
        $expense->pullDomainEvents(); // descartar ExpenseItemRejected

        $expense->resubmit();

        self::assertSame(ExpenseStatus::SUBMITTED, $expense->status());
        self::assertNotNull($expense->submittedAt());

        $events = $expense->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ExpenseResubmitted::class, $events[0]);
    }

    public function testThrowsWhenResubmittingNonRejectedExpense(): void
    {
        $expense = $this->createDraftExpense();

        $this->expectException(\InvalidArgumentException::class);
        $expense->resubmit();
    }

    public function testUpdatesDetails(): void
    {
        $expense = $this->createDraftExpense();

        $newStart = new DateTimeImmutable('2026-02-01');
        $newEnd = new DateTimeImmutable('2026-02-15');
        $expense->updateDetails('Nuevo motivo', $newStart, $newEnd);

        self::assertSame('Nuevo motivo', $expense->reason());
        self::assertSame($newStart, $expense->startDate());
        self::assertSame($newEnd, $expense->endDate());
    }

    public function testThrowsWhenUpdatingNonDraftExpense(): void
    {
        $expense = $this->createDraftExpense();
        $expense->addItem($this->createItemWithEvidence(self::ITEM_ID_1, 50.00));
        $expense->submit();

        $this->expectException(ExpenseNotEditableException::class);
        $expense->updateDetails(
            'Nuevo motivo',
            new DateTimeImmutable('2026-02-01'),
            new DateTimeImmutable('2026-02-15'),
        );
    }

    public function testSoftDeletesDraftExpense(): void
    {
        $expense = $this->createDraftExpense();
        $expense->softDelete();

        self::assertTrue($expense->isDeleted());
        self::assertNotNull($expense->deletedAt());
    }

    public function testThrowsWhenSoftDeletingSubmittedExpense(): void
    {
        $expense = $this->createDraftExpense();
        $expense->addItem($this->createItemWithEvidence(self::ITEM_ID_1, 50.00));
        $expense->submit();

        $this->expectException(\InvalidArgumentException::class);
        $expense->softDelete();
    }

    public function testRestores(): void
    {
        $expense = $this->createDraftExpense();
        $expense->softDelete();
        $expense->restore();

        self::assertFalse($expense->isDeleted());
        self::assertNull($expense->deletedAt());
    }

    // --- Helpers ---

    private function createDraftExpense(): OperatingExpense
    {
        return OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: 'EMP001',
            reason: 'Gastos de viaje',
            startDate: new DateTimeImmutable('2026-01-01'),
            endDate: new DateTimeImmutable('2026-01-15'),
        );
    }

    private function createItem(string $id, float $amount): ExpenseItem
    {
        return ExpenseItem::create(
            id: new ExpenseItemIdValueObject($id),
            type: ExpenseItemType::TAXI,
            amount: $amount,
        );
    }

    private function createItemWithEvidence(string $id, float $amount): ExpenseItem
    {
        $item = ExpenseItem::create(
            id: new ExpenseItemIdValueObject($id),
            type: ExpenseItemType::UBER_EMPRESARIAL,
            amount: $amount,
        );
        $item->uploadEvidence('/evidence/receipt.jpg');

        return $item;
    }

    private function createSubmittedExpenseWithEvidence(): OperatingExpense
    {
        $expense = $this->createDraftExpense();
        $expense->addItem($this->createItemWithEvidence(self::ITEM_ID_1, 50.00));
        $expense->addItem($this->createItemWithEvidence(self::ITEM_ID_2, 30.00));
        $expense->submit();

        return $expense;
    }

    private function createSubmittedExpenseWithSingleItem(): OperatingExpense
    {
        $expense = $this->createDraftExpense();
        $expense->addItem($this->createItemWithEvidence(self::ITEM_ID_1, 50.00));
        $expense->submit();

        return $expense;
    }

    private function createSubmittedExpenseWithReviewedItems(): OperatingExpense
    {
        $expense = $this->createSubmittedExpenseWithEvidence();
        foreach ($expense->items() as $item) {
            $item->approve('USER001');
        }

        return $expense;
    }
}
