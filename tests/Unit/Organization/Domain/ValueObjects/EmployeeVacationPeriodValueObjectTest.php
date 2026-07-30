<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmployeeVacationPeriodException;
use App\Organization\Domain\ValueObjects\EmployeeVacationPeriodValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EmployeeVacationPeriodValueObjectTest extends TestCase
{
    public function testAcceptsValidRange(): void
    {
        $vo = new EmployeeVacationPeriodValueObject(
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-07-15'),
        );

        self::assertSame('2024-07-01', $vo->startDate()->format('Y-m-d'));
        self::assertSame('2024-07-15', $vo->endDate()->format('Y-m-d'));
    }

    public function testAcceptsSameDay(): void
    {
        $vo = new EmployeeVacationPeriodValueObject(
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-07-01'),
        );

        self::assertSame(1, $vo->days());
    }

    public function testRejectsEndBeforeStart(): void
    {
        $this->expectException(EmployeeVacationPeriodException::class);
        $this->expectExceptionMessage('La fecha de fin debe ser mayor o igual');

        new EmployeeVacationPeriodValueObject(
            new DateTimeImmutable('2024-07-15'),
            new DateTimeImmutable('2024-07-01'),
        );
    }

    public function testDaysCalculatesCorrectly(): void
    {
        $vo = new EmployeeVacationPeriodValueObject(
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-07-15'),
        );

        self::assertSame(15, $vo->days());
    }

    public function testDaysReturnsOneForSameDay(): void
    {
        $vo = new EmployeeVacationPeriodValueObject(
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-07-01'),
        );

        self::assertSame(1, $vo->days());
    }

    public function testIncludeDateReturnsTrueForDateInRange(): void
    {
        $vo = new EmployeeVacationPeriodValueObject(
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-07-15'),
        );

        self::assertTrue($vo->includeDate(new DateTimeImmutable('2024-07-10')));
    }

    public function testIncludeDateReturnsTrueForStartDate(): void
    {
        $vo = new EmployeeVacationPeriodValueObject(
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-07-15'),
        );

        self::assertTrue($vo->includeDate(new DateTimeImmutable('2024-07-01')));
    }

    public function testIncludeDateReturnsTrueForEndDate(): void
    {
        $vo = new EmployeeVacationPeriodValueObject(
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-07-15'),
        );

        self::assertTrue($vo->includeDate(new DateTimeImmutable('2024-07-15')));
    }

    public function testIncludeDateReturnsFalseForBeforeRange(): void
    {
        $vo = new EmployeeVacationPeriodValueObject(
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-07-15'),
        );

        self::assertFalse($vo->includeDate(new DateTimeImmutable('2024-06-30')));
    }

    public function testIncludeDateReturnsFalseForAfterRange(): void
    {
        $vo = new EmployeeVacationPeriodValueObject(
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-07-15'),
        );

        self::assertFalse($vo->includeDate(new DateTimeImmutable('2024-07-16')));
    }
}
