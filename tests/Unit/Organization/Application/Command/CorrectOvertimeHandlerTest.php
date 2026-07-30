<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\CorrectOvertime\CorrectOvertimeCommand;
use App\Organization\Application\Command\CorrectOvertime\CorrectOvertimeHandler;
use App\Organization\Domain\Entities\OvertimeRecord;
use App\Organization\Domain\Enums\OvertimeStatus;
use App\Organization\Domain\Exceptions\OvertimeRecordException;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class CorrectOvertimeHandlerTest extends TestCase
{
    private const RECORD_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const EMPLOYEE_ID = '01HZ0000000000000000000001';
    private const REVIEWER_ID = '01HZ0000000000000000000002';

    public function testCorrectsOvertimeRecord(): void
    {
        $record = OvertimeRecord::register(
            id: OvertimeRecordIdValueObject::fromString(self::RECORD_ID),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            workedAt: new \DateTimeImmutable('2026-06-01'),
            startTime: new \DateTimeImmutable('2026-06-01 18:00'),
            endTime: new \DateTimeImmutable('2026-06-01 20:00'),
            reason: 'Trabajo extra original',
        );
        $record->pullDomainEvents(); // clean events from register()
        $record->requestReview(EmployeeIdValueObject::fromString(self::REVIEWER_ID));
        self::assertSame(OvertimeStatus::IN_REVIEW, $record->status());
        $record->pullDomainEvents(); // clean events from requestReview() to simulate Doctrine hydration

        $repo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $repo->method('findById')->willReturn($record);
        $repo->expects($this->once())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->exactly(1))->method('publish');

        $handler = new CorrectOvertimeHandler($repo, $publisher);
        ($handler)(new CorrectOvertimeCommand(
            overtimeRecordId: self::RECORD_ID,
            workedAt: '2026-06-01',
            startTime: '2026-06-01 19:00',
            endTime: '2026-06-01 22:00',
            reason: 'Trabajo extra corregido',
            evidenceUrl: 'https://example.com/evidence.jpg',
        ));

        self::assertSame(OvertimeStatus::PENDING, $record->status());
        self::assertSame('Trabajo extra corregido', $record->reason());
    }

    public function testThrowsWhenRecordNotFound(): void
    {
        $repo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new CorrectOvertimeHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(OvertimeRecordException::class);

        ($handler)(new CorrectOvertimeCommand(
            overtimeRecordId: '01HZ0000000000000000000099',
            workedAt: '2026-06-01',
            startTime: '2026-06-01 18:00',
            endTime: '2026-06-01 20:00',
            reason: 'Test',
        ));
    }
}
