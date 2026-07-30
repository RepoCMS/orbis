<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Domain\Entities;

use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\Enums\SettlementDirection;
use App\Expense\Domain\Events\CashAdvanceCreated;
use App\Expense\Domain\Events\CashAdvanceSettled;
use App\Expense\Domain\Events\CashAdvanceUpdated;
use App\Expense\Domain\Events\CashAdvancePendingReview;
use App\Expense\Domain\Exceptions\CashAdvanceNotOutstandingException;
use App\Expense\Domain\Exceptions\InvalidAmountException;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CashAdvanceTest extends TestCase
{
    private const ADVANCE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testCreatesCashAdvanceWithOutstandingStatus(): void
    {
        $now = new DateTimeImmutable();
        $advance = CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: 'USER001',
            amount: 200.00,
            givenAt: $now,
        );

        self::assertSame(self::ADVANCE_ID, (string) $advance->id());
        self::assertSame('CA-2026-0001', $advance->advanceNumber());
        self::assertSame('EMP001', $advance->employeeId());
        self::assertSame('USER001', $advance->givenBy());
        self::assertSame(200.00, $advance->amount());
        self::assertSame($now, $advance->givenAt());
        self::assertSame(CashAdvanceStatus::OUTSTANDING, $advance->status());
    }

    public function testRecordsCashAdvanceCreatedEvent(): void
    {
        $advance = CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: 'USER001',
            amount: 200.00,
            givenAt: new DateTimeImmutable(),
        );

        $events = $advance->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CashAdvanceCreated::class, $events[0]);
    }

    public function testThrowsWhenEmployeeIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: '',
            givenBy: 'USER001',
            amount: 200.00,
            givenAt: new DateTimeImmutable(),
        );
    }

    public function testThrowsWhenGivenByIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: '',
            amount: 200.00,
            givenAt: new DateTimeImmutable(),
        );
    }

    public function testThrowsWhenAmountIsZero(): void
    {
        $this->expectException(InvalidAmountException::class);
        CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: 'USER001',
            amount: 0,
            givenAt: new DateTimeImmutable(),
        );
    }

    public function testSettlesCashAdvance(): void
    {
        $advance = $this->createOutstandingAdvance();
        $advance->pullDomainEvents(); // descartar CashAdvanceCreated
        $advance->settle('USER002', SettlementDirection::TECH_TO_COMPANY);

        self::assertSame(CashAdvanceStatus::SETTLED, $advance->status());
        self::assertSame('USER002', $advance->settlementConfirmedBy());
        self::assertNotNull($advance->settlementConfirmedAt());
        self::assertSame(SettlementDirection::TECH_TO_COMPANY, $advance->settlementDirection());

        $events = $advance->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CashAdvanceSettled::class, $events[0]);
    }

    public function testThrowsWhenSettlingNonOutstandingAdvance(): void
    {
        $advance = $this->createOutstandingAdvance();
        $advance->pullDomainEvents();
        $advance->settle('USER002', SettlementDirection::TECH_TO_COMPANY);

        $this->expectException(CashAdvanceNotOutstandingException::class);
        $advance->settle('USER003', SettlementDirection::COMPANY_TO_TECH);
    }

    public function testMarkPendingReview(): void
    {
        $advance = $this->createOutstandingAdvance();
        $advance->pullDomainEvents();
        $advance->markPendingReview();

        self::assertSame(CashAdvanceStatus::PENDING_REVIEW, $advance->status());

        $events = $advance->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CashAdvancePendingReview::class, $events[0]);
    }

    public function testIsOverdueReturnsTrueWhenPastLimit(): void
    {
        $givenAt = new DateTimeImmutable('-5 days');
        $advance = CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: 'USER001',
            amount: 200.00,
            givenAt: $givenAt,
        );

        self::assertTrue($advance->isOverdue(3));
    }

    public function testIsOverdueReturnsFalseWhenWithinLimit(): void
    {
        $givenAt = new DateTimeImmutable('-1 day');
        $advance = CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: 'USER001',
            amount: 200.00,
            givenAt: $givenAt,
        );

        self::assertFalse($advance->isOverdue(3));
    }

    public function testIsOverdueReturnsFalseWhenSettled(): void
    {
        $givenAt = new DateTimeImmutable('-5 days');
        $advance = CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: 'USER001',
            amount: 200.00,
            givenAt: $givenAt,
        );
        $advance->settle('USER002', SettlementDirection::TECH_TO_COMPANY);

        self::assertFalse($advance->isOverdue(3));
    }

    public function testUploadsVoucher(): void
    {
        $advance = $this->createOutstandingAdvance();
        $advance->uploadVoucher('/uploads/vouchers/receipt.pdf');

        self::assertSame('/uploads/vouchers/receipt.pdf', $advance->voucherPath());
    }

    public function testThrowsWhenUploadingEmptyVoucherPath(): void
    {
        $advance = $this->createOutstandingAdvance();

        $this->expectException(\InvalidArgumentException::class);
        $advance->uploadVoucher('');
    }

    public function testUpdatesAmount(): void
    {
        $advance = $this->createOutstandingAdvance();
        $advance->pullDomainEvents();
        $advance->updateAmount(150.00);

        self::assertSame(150.00, $advance->amount());

        $events = $advance->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CashAdvanceUpdated::class, $events[0]);
    }

    public function testThrowsWhenUpdatingAmountOnNonOutstandingAdvance(): void
    {
        $advance = $this->createOutstandingAdvance();
        $advance->settle('USER002', SettlementDirection::TECH_TO_COMPANY);

        $this->expectException(CashAdvanceNotOutstandingException::class);
        $advance->updateAmount(100.00);
    }

    public function testThrowsWhenUpdatingAmountToZero(): void
    {
        $advance = $this->createOutstandingAdvance();

        $this->expectException(InvalidAmountException::class);
        $advance->updateAmount(0);
    }

    public function testSoftDeletesOutstandingAdvance(): void
    {
        $advance = $this->createOutstandingAdvance();
        $advance->softDelete();

        self::assertTrue($advance->isDeleted());
    }

    public function testThrowsWhenDeletingSettledAdvance(): void
    {
        $advance = $this->createOutstandingAdvance();
        $advance->settle('USER002', SettlementDirection::TECH_TO_COMPANY);

        $this->expectException(\InvalidArgumentException::class);
        $advance->softDelete();
    }

    public function testRestores(): void
    {
        $advance = $this->createOutstandingAdvance();
        $advance->softDelete();
        $advance->restore();

        self::assertFalse($advance->isDeleted());
        self::assertNull($advance->deletedAt());
    }

    private function createOutstandingAdvance(): CashAdvance
    {
        return CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: 'USER001',
            amount: 200.00,
            givenAt: new DateTimeImmutable(),
        );
    }
}
