<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entities;

use App\Organization\Domain\Entities\EmployeeSpecialPeriod;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodStatus;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodException;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EmployeeSpecialPeriodTest extends TestCase
{
    private const EMPLOYEE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const PERIOD_ID   = '01DXZ6NDEKTSV4RRFFQ69G5ABC';

    // ── Schedule (Creación) ─────────────────────────────────────────────────

    public function testScheduleCreatesScheduledStatusForFutureDate(): void
    {
        $period = EmployeeSpecialPeriod::schedule(
            id:         new EmployeeSpecialPeriodIdValueObject(self::PERIOD_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            type:       EmployeeSpecialPeriodType::VACATION,
            startedAt:  new DateTimeImmutable('+10 days'),
            endedAt:    new DateTimeImmutable('+15 days'),
        );

        self::assertSame(EmployeeSpecialPeriodStatus::SCHEDULED, $period->status());
        self::assertSame(self::PERIOD_ID, (string) $period->id());
        self::assertSame(self::EMPLOYEE_ID, (string) $period->employeeId());
        self::assertSame(EmployeeSpecialPeriodType::VACATION, $period->type());
    }

    public function testScheduleCreatesInProgressStatusForPastDate(): void
    {
        $period = EmployeeSpecialPeriod::schedule(
            id:         new EmployeeSpecialPeriodIdValueObject(self::PERIOD_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            type:       EmployeeSpecialPeriodType::STANDBY,
            startedAt:  new DateTimeImmutable('-5 days'),
            endedAt:    new DateTimeImmutable('+2 days'),
        );

        self::assertSame(EmployeeSpecialPeriodStatus::IN_PROGRESS, $period->status());
    }

    public function testScheduleThrowsWhenEndedAtEqualsStartedAt(): void
    {
        $this->expectException(EmployeeSpecialPeriodException::class);
        $this->expectExceptionMessage('La fecha de fin debe ser posterior a la fecha de inicio del período.');

        EmployeeSpecialPeriod::schedule(
            id:         new EmployeeSpecialPeriodIdValueObject(self::PERIOD_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            type:       EmployeeSpecialPeriodType::VACATION,
            startedAt:  new DateTimeImmutable('2025-01-15'),
            endedAt:    new DateTimeImmutable('2025-01-15'),
        );
    }

    public function testScheduleThrowsWhenEndedAtBeforeStartedAt(): void
    {
        $this->expectException(EmployeeSpecialPeriodException::class);
        $this->expectExceptionMessage('La fecha de fin debe ser posterior a la fecha de inicio del período.');

        EmployeeSpecialPeriod::schedule(
            id:         new EmployeeSpecialPeriodIdValueObject(self::PERIOD_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            type:       EmployeeSpecialPeriodType::VACATION,
            startedAt:  new DateTimeImmutable('2025-01-20'),
            endedAt:    new DateTimeImmutable('2025-01-15'),
        );
    }

    // ── Begin (SCHEDULED → IN_PROGRESS) ────────────────────────────────────

    public function testBeginTransitionsToInProgress(): void
    {
        $period = $this->createScheduledPeriod();

        $period->begin();

        self::assertSame(EmployeeSpecialPeriodStatus::IN_PROGRESS, $period->status());
    }

    public function testBeginThrowsWhenNotScheduled(): void
    {
        $period = $this->createScheduledPeriod();
        $period->begin(); // ahora está IN_PROGRESS

        $this->expectException(EmployeeSpecialPeriodException::class);
        $this->expectExceptionMessage('Solo se puede iniciar un período programado.');

        $period->begin();
    }

    // ── Complete (IN_PROGRESS → COMPLETED) ──────────────────────────────────

    public function testCompleteTransitionsToCompleted(): void
    {
        $period = $this->createInProgressPeriod();

        $period->complete();

        self::assertSame(EmployeeSpecialPeriodStatus::COMPLETED, $period->status());
    }

    public function testCompleteThrowsWhenNotInProgress(): void
    {
        $period = $this->createScheduledPeriod();

        $this->expectException(EmployeeSpecialPeriodException::class);
        $this->expectExceptionMessage('Solo se puede completar un período en curso.');

        $period->complete();
    }

    // ── Cancel ──────────────────────────────────────────────────────────────

    public function testCancelScheduledPeriod(): void
    {
        $period = $this->createScheduledPeriod();

        $period->cancel();

        self::assertSame(EmployeeSpecialPeriodStatus::CANCELLED, $period->status());
    }

    public function testCancelInProgressPeriod(): void
    {
        $period = $this->createInProgressPeriod();

        $period->cancel();

        self::assertSame(EmployeeSpecialPeriodStatus::CANCELLED, $period->status());
    }

    public function testCancelThrowsWhenAlreadyCompleted(): void
    {
        $period = $this->createInProgressPeriod();
        $period->complete();

        $this->expectException(EmployeeSpecialPeriodException::class);
        $this->expectExceptionMessage('No se puede cancelar un período ya completado o cancelado.');

        $period->cancel();
    }

    public function testCancelThrowsWhenAlreadyCancelled(): void
    {
        $period = $this->createScheduledPeriod();
        $period->cancel();

        $this->expectException(EmployeeSpecialPeriodException::class);
        $this->expectExceptionMessage('No se puede cancelar un período ya completado o cancelado.');

        $period->cancel();
    }

    // ── Days ────────────────────────────────────────────────────────────────

    public function testDaysReturnsCorrectCount(): void
    {
        $period = EmployeeSpecialPeriod::schedule(
            id:         new EmployeeSpecialPeriodIdValueObject(self::PERIOD_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            type:       EmployeeSpecialPeriodType::VACATION,
            startedAt:  new DateTimeImmutable('2025-01-15'),
            endedAt:    new DateTimeImmutable('2025-01-20'),
        );

        // 15,16,17,18,19,20 = 6 días
        self::assertSame(6, $period->days());
    }

    // ── Status transitions via edit when past date ──────────────────────────

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function createScheduledPeriod(): EmployeeSpecialPeriod
    {
        return EmployeeSpecialPeriod::schedule(
            id:         new EmployeeSpecialPeriodIdValueObject(self::PERIOD_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            type:       EmployeeSpecialPeriodType::VACATION,
            startedAt:  new DateTimeImmutable('+10 days'),
            endedAt:    new DateTimeImmutable('+15 days'),
        );
    }

    private function createInProgressPeriod(): EmployeeSpecialPeriod
    {
        return EmployeeSpecialPeriod::schedule(
            id:         new EmployeeSpecialPeriodIdValueObject(self::PERIOD_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            type:       EmployeeSpecialPeriodType::VACATION,
            startedAt:  new DateTimeImmutable('-5 days'),
            endedAt:    new DateTimeImmutable('+2 days'),
        );
    }
}
