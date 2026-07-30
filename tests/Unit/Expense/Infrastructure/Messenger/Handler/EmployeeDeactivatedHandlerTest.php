<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Infrastructure\Messenger\Handler;

use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\Enums\SettlementDirection;
use App\Expense\Domain\Events\CashAdvancePendingReview;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Expense\Infrastructure\Messenger\Handler\EmployeeDeactivatedHandler;
use App\Organization\Domain\Events\EmployeeDeactivated;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EmployeeDeactivatedHandlerTest extends TestCase
{
    private const EMPLOYEE_ID = 'EMP-001';

    private function createOutstandingAdvance(string $id): CashAdvance
    {
        return CashAdvance::create(
            id: new CashAdvanceIdValueObject($id),
            advanceNumber: 'ADV-2026-' . str_pad(substr($id, -3), 4, '0', STR_PAD_LEFT),
            employeeId: self::EMPLOYEE_ID,
            givenBy: 'COORD-001',
            amount: 500.0,
            givenAt: new DateTimeImmutable('-3 days'),
        );
    }

    private function createSettledAdvance(string $id): CashAdvance
    {
        $advance = $this->createOutstandingAdvance($id);
        $advance->settle('COORD-001', SettlementDirection::COMPANY_TO_TECH);

        return $advance;
    }

    public function testMarksOutstandingAdvancesAsPendingReview(): void
    {
        $outstandingAdvance = $this->createOutstandingAdvance('01HZ0000000000000000000010');
        $settledAdvance = $this->createSettledAdvance('01HZ0000000000000000000011');

        $cashAdvanceRepo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $cashAdvanceRepo->method('findByEmployeeId')
            ->with(self::EMPLOYEE_ID)
            ->willReturn([$outstandingAdvance, $settledAdvance]);

        $savedAdvances = [];
        $cashAdvanceRepo->expects($this->exactly(1))
            ->method('save')
            ->willReturnCallback(function (CashAdvance $advance) use (&$savedAdvances): void {
                $savedAdvances[] = $advance;
            });

        $handler = new EmployeeDeactivatedHandler($cashAdvanceRepo);

        $event = new EmployeeDeactivated(employeeId: self::EMPLOYEE_ID, userId: 'USER-001');
        $handler(DomainEventMessage::fromEvent($event));

        self::assertCount(1, $savedAdvances);
        self::assertSame($outstandingAdvance, $savedAdvances[0]);
        self::assertSame(CashAdvanceStatus::PENDING_REVIEW, $outstandingAdvance->status());
        self::assertSame(CashAdvanceStatus::SETTLED, $settledAdvance->status());
    }

    public function testNoOpWhenNoAdvances(): void
    {
        $cashAdvanceRepo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $cashAdvanceRepo->method('findByEmployeeId')
            ->with(self::EMPLOYEE_ID)
            ->willReturn([]);
        $cashAdvanceRepo->expects($this->never())->method('save');

        $handler = new EmployeeDeactivatedHandler($cashAdvanceRepo);

        $event = new EmployeeDeactivated(employeeId: self::EMPLOYEE_ID, userId: 'USER-001');
        $handler(DomainEventMessage::fromEvent($event));
    }

    public function testIgnoresNonEmployeeDeactivatedEvents(): void
    {
        $cashAdvanceRepo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $cashAdvanceRepo->expects($this->never())->method('findByEmployeeId');
        $cashAdvanceRepo->expects($this->never())->method('save');

        $handler = new EmployeeDeactivatedHandler($cashAdvanceRepo);

        $otherEvent = new CashAdvancePendingReview(
            cashAdvanceId: '01HZ0000000000000000000020',
            employeeId: self::EMPLOYEE_ID,
            givenBy: 'COORD-001',
            amount: 500.0,
        );
        $handler(DomainEventMessage::fromEvent($otherEvent));
    }
}
