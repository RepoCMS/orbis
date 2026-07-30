<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\RequestOvertimeCorrection\RequestOvertimeCorrectionCommand;
use App\Organization\Application\Command\RequestOvertimeCorrection\RequestOvertimeCorrectionHandler;
use App\Organization\Domain\Entities\OvertimeRecord;
use App\Organization\Domain\Enums\OvertimeStatus;
use App\Organization\Domain\Exceptions\OvertimeRecordException;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class RequestOvertimeCorrectionHandlerTest extends TestCase
{
    private const RECORD_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const EMPLOYEE_ID = '01HZ0000000000000000000001';
    private const REVIEWER_ID = '01HZ0000000000000000000002';

    public function testRequestsCorrection(): void
    {
        $record = OvertimeRecord::register(
            id: OvertimeRecordIdValueObject::fromString(self::RECORD_ID),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            workedAt: new \DateTimeImmutable('2026-06-01'),
            startTime: new \DateTimeImmutable('2026-06-01 18:00'),
            endTime: new \DateTimeImmutable('2026-06-01 20:00'),
            reason: 'Trabajo extra',
        );
        self::assertSame(OvertimeStatus::PENDING, $record->status());
        // Simulate Doctrine hydration: domain events from constructor are not persisted
        $record->pullDomainEvents();

        $repo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $repo->method('findById')->willReturn($record);
        $repo->expects($this->once())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(\App\Organization\Domain\Events\OvertimeReviewRequested::class));

        $handler = new RequestOvertimeCorrectionHandler($repo, $publisher);
        ($handler)(new RequestOvertimeCorrectionCommand(
            overtimeRecordId: self::RECORD_ID,
            reviewedBy: self::REVIEWER_ID,
            correctionHint: 'Corregir horas',
        ));

        self::assertSame(OvertimeStatus::IN_REVIEW, $record->status());
    }

    public function testThrowsWhenRecordNotFound(): void
    {
        $repo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new RequestOvertimeCorrectionHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(OvertimeRecordException::class);

        ($handler)(new RequestOvertimeCorrectionCommand(
            overtimeRecordId: '01HZ0000000000000000000099',
            reviewedBy: self::REVIEWER_ID,
        ));
    }
}
