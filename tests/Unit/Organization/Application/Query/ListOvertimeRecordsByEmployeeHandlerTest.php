<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Query;

use App\Organization\Application\Query\ListOvertimeRecordsByEmployee\ListOvertimeRecordsByEmployeeHandler;
use App\Organization\Application\Query\ListOvertimeRecordsByEmployee\ListOvertimeRecordsByEmployeeQuery;
use App\Organization\Application\Query\Response\OvertimeRecordResponse;
use App\Organization\Domain\Entities\OvertimeRecord;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListOvertimeRecordsByEmployeeHandlerTest extends TestCase
{
    private const EMPLOYEE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testReturnsRecords(): void
    {
        $record = OvertimeRecord::register(
            id: OvertimeRecordIdValueObject::fromString('01HZ0000000000000000000001'),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            workedAt: new \DateTimeImmutable('2026-06-01'),
            startTime: new \DateTimeImmutable('2026-06-01 18:00'),
            endTime: new \DateTimeImmutable('2026-06-01 20:00'),
            reason: 'Trabajo extra',
        );

        $repo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $repo->method('findByEmployee')->willReturn([$record]);

        $handler = new ListOvertimeRecordsByEmployeeHandler($repo);
        $result = ($handler)(new ListOvertimeRecordsByEmployeeQuery(
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertCount(1, $result);
        self::assertInstanceOf(OvertimeRecordResponse::class, $result[0]);
    }

    public function testReturnsEmptyWhenNoRecords(): void
    {
        $repo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $repo->method('findByEmployee')->willReturn([]);

        $handler = new ListOvertimeRecordsByEmployeeHandler($repo);
        $result = ($handler)(new ListOvertimeRecordsByEmployeeQuery(
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertCount(0, $result);
    }
}
