<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\RejectOvertime\RejectOvertimeCommand;
use App\Organization\Application\Command\RejectOvertime\RejectOvertimeHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Entities\OvertimeRecord;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\OvertimeStatus;
use App\Organization\Domain\Events\OvertimeRejected;
use App\Organization\Domain\Exceptions\OvertimeRecordException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RejectOvertimeHandlerTest extends TestCase
{
    private const RECORD_ID    = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const EMPLOYEE_ID  = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
    private const REVIEWER_ID  = '01ARZ3NDEKTSV4RRFFQ69G5FAX';
    private const VALID_ID     = '1710034065';

    private function pendingRecord(): OvertimeRecord
    {
        return OvertimeRecord::register(
            id:         new OvertimeRecordIdValueObject(self::RECORD_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            workedAt:   new DateTimeImmutable('-2 days'),
            startTime:  new DateTimeImmutable('-2 days 18:00'),
            endTime:    new DateTimeImmutable('-2 days 20:30'),
            reason:     'Reparación urgente',
        );
    }

    private function makeReviewer(): Employee
    {
        return Employee::hire(
            id:                   new EmployeeIdValueObject(self::REVIEWER_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject(self::VALID_ID),
            firstName:            'María Elena',
            lastName:             'González Ruiz',
            email:                new EmployeeEmailValueObject('maria.gonzalez@example.com'),
            phoneNumber:          '0998765432',
            hiredAt:              new \DateTimeImmutable('2023-01-01'),
            profile:              EmployeeProfile::TECHNICIAN,
        );
    }

    public function testRejectsOvertimeSuccessfully(): void
    {
        $record = $this->pendingRecord();

        $overtimeRepo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $overtimeRepo->method('findById')->willReturn($record);
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($this->makeReviewer());
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $savedRecord = null;
        $overtimeRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (OvertimeRecord $r) use (&$savedRecord): void {
                $savedRecord = $r;
            });

        $publishedEvents = [];
        $publisher->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (object $event) use (&$publishedEvents): void {
                $publishedEvents[] = $event;
            });

        $handler = new RejectOvertimeHandler($overtimeRepo, $employeeRepo, $publisher);
        ($handler)(new RejectOvertimeCommand(
            overtimeRecordId: self::RECORD_ID,
            reviewedBy:       self::REVIEWER_ID,
            reason:           'No corresponde a horas extras',
        ));

        self::assertNotNull($savedRecord);
        self::assertSame(OvertimeStatus::REJECTED, $savedRecord->status());
        self::assertSame('No corresponde a horas extras', $savedRecord->rejectionReason());
        self::assertCount(2, $publishedEvents);
        self::assertInstanceOf(OvertimeRejected::class, $publishedEvents[1]);
    }

    public function testThrowsWhenRecordNotFound(): void
    {
        $overtimeRepo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $overtimeRepo->method('findById')->willReturn(null);
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $overtimeRepo->expects($this->never())->method('save');

        $handler = new RejectOvertimeHandler($overtimeRepo, $employeeRepo, $publisher);

        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('Registro de horas extras no encontrado.');

        ($handler)(new RejectOvertimeCommand(
            overtimeRecordId: self::RECORD_ID,
            reviewedBy:       self::REVIEWER_ID,
            reason:           'No corresponde',
        ));
    }

    public function testThrowsWhenReviewerNotFound(): void
    {
        $record = $this->pendingRecord();

        $overtimeRepo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $overtimeRepo->method('findById')->willReturn($record);
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn(null);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RejectOvertimeHandler($overtimeRepo, $employeeRepo, $publisher);

        $this->expectException(EmployeeNotFoundException::class);

        ($handler)(new RejectOvertimeCommand(
            overtimeRecordId: self::RECORD_ID,
            reviewedBy:       self::REVIEWER_ID,
            reason:           'No corresponde',
        ));
    }

    public function testThrowsWhenAlreadyRejected(): void
    {
        $record = $this->pendingRecord();
        $record->reject(new EmployeeIdValueObject(self::REVIEWER_ID), 'Primer rechazo');

        $overtimeRepo = $this->createMock(OvertimeRecordRepositoryInterface::class);
        $overtimeRepo->method('findById')->willReturn($record);
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($this->makeReviewer());
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RejectOvertimeHandler($overtimeRepo, $employeeRepo, $publisher);

        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('Solo se puede rechazar un registro pendiente.');

        ($handler)(new RejectOvertimeCommand(
            overtimeRecordId: self::RECORD_ID,
            reviewedBy:       self::REVIEWER_ID,
            reason:           'Segundo rechazo',
        ));
    }
}
