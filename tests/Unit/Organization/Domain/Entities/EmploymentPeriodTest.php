<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entities;

use App\Organization\Domain\Entities\EmploymentPeriod;
use App\Organization\Domain\Enums\EmploymentPeriodStatus;
use App\Organization\Domain\Exceptions\EmploymentPeriodException;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmploymentPeriodIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EmploymentPeriodTest extends TestCase
{
    private function createPeriod(): EmploymentPeriod
    {
        return EmploymentPeriod::start(
            new EmploymentPeriodIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2024-01-15'),
        );
    }

    public function testStartCreatesActivePeriod(): void
    {
        $period = $this->createPeriod();

        self::assertSame(EmploymentPeriodStatus::ACTIVE, $period->status());
        self::assertTrue($period->isActive());
        self::assertNull($period->endedAt());
        self::assertFalse($period->isDeleted());
    }

    public function testStartWithDefaultStartedAt(): void
    {
        $period = EmploymentPeriod::start(
            new EmploymentPeriodIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
        );

        self::assertNotNull($period->startedAt());
    }

    public function testStartStoresAllFields(): void
    {
        $period = $this->createPeriod();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $period->id());
        self::assertSame('01BXZ4MDEKTSV4RRFFQ69G5ABC', (string) $period->employeeId());
        self::assertSame('2024-01-15', $period->startedAt()->format('Y-m-d'));
        self::assertNotNull($period->createdAt());
    }

    public function testCloseSetsClosedStatusAndEndedAt(): void
    {
        $period = $this->createPeriod();
        $endedAt = new DateTimeImmutable('2024-12-31');

        $period->close($endedAt);

        self::assertSame(EmploymentPeriodStatus::CLOSED, $period->status());
        self::assertFalse($period->isActive());
        self::assertSame('2024-12-31', $period->endedAt()->format('Y-m-d'));
    }

    public function testCloseThrowsWhenAlreadyClosed(): void
    {
        $period = $this->createPeriod();
        $period->close(new DateTimeImmutable('2024-12-31'));

        $this->expectException(EmploymentPeriodException::class);
        $this->expectExceptionMessage('Solo se puede cerrar un período de empleo activo.');

        $period->close(new DateTimeImmutable('2025-01-15'));
    }

    public function testCloseThrowsWhenEndedAtBeforeStartedAt(): void
    {
        $period = $this->createPeriod();

        $this->expectException(EmploymentPeriodException::class);
        $this->expectExceptionMessage('no puede ser anterior a la fecha de inicio');

        $period->close(new DateTimeImmutable('2023-01-01'));
    }
}
