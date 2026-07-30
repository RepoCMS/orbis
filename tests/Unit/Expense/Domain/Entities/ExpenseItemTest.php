<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Domain\Entities;

use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Enums\ExpenseItemStatus;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Exceptions\InvalidAmountException;
use App\Expense\Domain\Exceptions\ItemAlreadyApprovedException;
use App\Expense\Domain\Exceptions\MissingRejectionReasonException;
use App\Expense\Domain\Exceptions\ItemNotInPendingStatusException;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class ExpenseItemTest extends TestCase
{
    private const ITEM_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testCreatesExpenseItemWithPendingStatus(): void
    {
        $item = ExpenseItem::create(
            id: new ExpenseItemIdValueObject(self::ITEM_ID),
            type: ExpenseItemType::TAXI,
            amount: 25.50,
        );

        self::assertSame(self::ITEM_ID, (string) $item->id());
        self::assertSame(ExpenseItemType::TAXI, $item->type());
        self::assertSame(25.50, $item->amount());
        self::assertFalse($item->evidenceRequired());
        self::assertSame(ExpenseItemStatus::PENDING, $item->status());
        self::assertNull($item->evidencePath());
        self::assertNull($item->rejectionReason());
        self::assertNull($item->reviewedBy());
        self::assertNull($item->reviewedAt());
    }

    public function testCreatesExpenseItemWithEvidenceRequired(): void
    {
        $item = ExpenseItem::create(
            id: new ExpenseItemIdValueObject(self::ITEM_ID),
            type: ExpenseItemType::UBER_EMPRESARIAL,
            amount: 15.00,
            ticketIds: ['TICKET001'],
            notes: 'Viaje a cliente',
        );

        self::assertTrue($item->evidenceRequired());
        self::assertSame(['TICKET001'], $item->ticketIds());
        self::assertSame('Viaje a cliente', $item->notes());
    }

    public function testThrowsWhenAmountIsZero(): void
    {
        $this->expectException(InvalidAmountException::class);

        ExpenseItem::create(
            id: new ExpenseItemIdValueObject(self::ITEM_ID),
            type: ExpenseItemType::TAXI,
            amount: 0,
        );
    }

    public function testThrowsWhenAmountIsNegative(): void
    {
        $this->expectException(InvalidAmountException::class);

        ExpenseItem::create(
            id: new ExpenseItemIdValueObject(self::ITEM_ID),
            type: ExpenseItemType::TAXI,
            amount: -10,
        );
    }

    public function testApprovesItem(): void
    {
        $item = $this->createPendingItem();
        $item->approve('USER001');

        self::assertSame(ExpenseItemStatus::APPROVED, $item->status());
        self::assertSame('USER001', $item->reviewedBy());
        self::assertNotNull($item->reviewedAt());
    }

    public function testThrowsWhenApprovingAlreadyReviewedItem(): void
    {
        $item = $this->createPendingItem();
        $item->approve('USER001');

        $this->expectException(ItemAlreadyApprovedException::class);
        $item->approve('USER002');
    }

    public function testRejectsItem(): void
    {
        $item = $this->createPendingItem();
        $item->reject('Comprobante ilegible', 'USER001');

        self::assertSame(ExpenseItemStatus::REJECTED, $item->status());
        self::assertSame('Comprobante ilegible', $item->rejectionReason());
        self::assertSame('USER001', $item->reviewedBy());
        self::assertNotNull($item->reviewedAt());
    }

    public function testThrowsWhenRejectingAlreadyReviewedItem(): void
    {
        $item = $this->createPendingItem();
        $item->reject('Comprobante ilegible', 'USER001');

        $this->expectException(ItemAlreadyApprovedException::class);
        $item->reject('Otro motivo', 'USER002');
    }

    public function testThrowsWhenRejectingWithoutReason(): void
    {
        $item = $this->createPendingItem();

        $this->expectException(MissingRejectionReasonException::class);
        $item->reject('', 'USER001');
    }

    public function testResetsForResubmit(): void
    {
        $item = $this->createPendingItem();
        $item->reject('Comprobante ilegible', 'USER001');

        self::assertSame(ExpenseItemStatus::REJECTED, $item->status());

        $item->resetForResubmit();

        self::assertSame(ExpenseItemStatus::PENDING, $item->status());
        self::assertNull($item->rejectionReason());
        self::assertNull($item->reviewedBy());
        self::assertNull($item->reviewedAt());
    }

    public function testThrowsWhenResettingNonRejectedItem(): void
    {
        $item = $this->createPendingItem();

        $this->expectException(ItemNotInPendingStatusException::class);
        $item->resetForResubmit();
    }

    public function testUploadsEvidence(): void
    {
        $item = $this->createPendingItem();
        $item->uploadEvidence('/uploads/evidence/receipt.jpg');

        self::assertSame('/uploads/evidence/receipt.jpg', $item->evidencePath());
    }

    public function testThrowsWhenUploadingEmptyPath(): void
    {
        $item = $this->createPendingItem();

        $this->expectException(\InvalidArgumentException::class);
        $item->uploadEvidence('');
    }

    public function testSoftDeletes(): void
    {
        $item = $this->createPendingItem();
        $item->softDelete();

        self::assertTrue($item->isDeleted());
        self::assertNotNull($item->deletedAt());
    }

    public function testRestores(): void
    {
        $item = $this->createPendingItem();
        $item->softDelete();
        $item->restore();

        self::assertFalse($item->isDeleted());
        self::assertNull($item->deletedAt());
    }

    private function createPendingItem(): ExpenseItem
    {
        return ExpenseItem::create(
            id: new ExpenseItemIdValueObject(self::ITEM_ID),
            type: ExpenseItemType::UBER_EMPRESARIAL,
            amount: 25.00,
        );
    }
}
