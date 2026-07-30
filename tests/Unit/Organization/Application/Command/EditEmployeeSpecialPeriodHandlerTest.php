<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\EditEmployeeSpecialPeriod\EditEmployeeSpecialPeriodCommand;
use App\Organization\Application\Command\EditEmployeeSpecialPeriod\EditEmployeeSpecialPeriodHandler;
use App\Organization\Domain\Entities\EmployeeSpecialPeriod;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodException;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use PHPUnit\Framework\TestCase;

final class EditEmployeeSpecialPeriodHandlerTest extends TestCase
{
    private const PERIOD_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const EMPLOYEE_ID = '01HZ0000000000000000000001';

    public function testEditsPeriod(): void
    {
        $period = EmployeeSpecialPeriod::schedule(
            id: EmployeeSpecialPeriodIdValueObject::fromString(self::PERIOD_ID),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            type: EmployeeSpecialPeriodType::STANDBY,
            startedAt: new \DateTimeImmutable('2026-07-01'),
            endedAt: new \DateTimeImmutable('2026-07-02'),
        );

        $repo = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $repo->method('findById')->willReturn($period);
        $repo->expects($this->once())->method('save');

        $handler = new EditEmployeeSpecialPeriodHandler($repo);
        ($handler)(new EditEmployeeSpecialPeriodCommand(
            periodId: self::PERIOD_ID,
            employeeId: self::EMPLOYEE_ID,
            startedAt: '2026-07-05',
            endedAt: '2026-07-10',
            type: 'vacation',
            notes: 'Editado',
        ));

        self::assertSame(EmployeeSpecialPeriodType::VACATION, $period->type());
    }

    public function testThrowsWhenPeriodNotFound(): void
    {
        $repo = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new EditEmployeeSpecialPeriodHandler($repo);

        $this->expectException(EmployeeSpecialPeriodException::class);

        ($handler)(new EditEmployeeSpecialPeriodCommand(
            periodId: '01HZ0000000000000000000099',
            employeeId: self::EMPLOYEE_ID,
            startedAt: '2026-07-05',
            endedAt: '2026-07-10',
            type: 'vacation',
        ));
    }

    public function testThrowsWhenPeriodDoesNotBelongToEmployee(): void
    {
        $period = EmployeeSpecialPeriod::schedule(
            id: EmployeeSpecialPeriodIdValueObject::fromString(self::PERIOD_ID),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            type: EmployeeSpecialPeriodType::STANDBY,
            startedAt: new \DateTimeImmutable('2026-07-01'),
            endedAt: new \DateTimeImmutable('2026-07-02'),
        );

        $repo = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $repo->method('findById')->willReturn($period);

        $handler = new EditEmployeeSpecialPeriodHandler($repo);

        $this->expectException(EmployeeSpecialPeriodException::class);

        ($handler)(new EditEmployeeSpecialPeriodCommand(
            periodId: self::PERIOD_ID,
            employeeId: '01HZ0000000000000000000099',
            startedAt: '2026-07-05',
            endedAt: '2026-07-10',
            type: 'vacation',
        ));
    }
}
