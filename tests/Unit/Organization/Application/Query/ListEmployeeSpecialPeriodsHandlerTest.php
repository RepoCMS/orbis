<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Query;

use App\Organization\Application\Query\ListEmployeeSpecialPeriods\ListEmployeeSpecialPeriodsHandler;
use App\Organization\Application\Query\ListEmployeeSpecialPeriods\ListEmployeeSpecialPeriodsQuery;
use App\Organization\Application\Query\Response\EmployeeSpecialPeriodResponse;
use App\Organization\Domain\Entities\EmployeeSpecialPeriod;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListEmployeeSpecialPeriodsHandlerTest extends TestCase
{
    private const EMPLOYEE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testReturnsPeriodsForEmployee(): void
    {
        $period = EmployeeSpecialPeriod::schedule(
            id: EmployeeSpecialPeriodIdValueObject::fromString('01HZ0000000000000000000001'),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            type: EmployeeSpecialPeriodType::VACATION,
            startedAt: new \DateTimeImmutable('2026-07-01'),
            endedAt: new \DateTimeImmutable('2026-07-15'),
        );

        $repo = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $repo->method('findAllByEmployee')->willReturn([$period]);

        $handler = new ListEmployeeSpecialPeriodsHandler($repo);
        $result = ($handler)(new ListEmployeeSpecialPeriodsQuery(
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertCount(1, $result);
        self::assertInstanceOf(EmployeeSpecialPeriodResponse::class, $result[0]);
    }

    public function testReturnsEmptyWhenNoPeriods(): void
    {
        $repo = $this->createMock(EmployeeSpecialPeriodRepositoryInterface::class);
        $repo->method('findAllByEmployee')->willReturn([]);

        $handler = new ListEmployeeSpecialPeriodsHandler($repo);
        $result = ($handler)(new ListEmployeeSpecialPeriodsQuery(
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertCount(0, $result);
    }
}
