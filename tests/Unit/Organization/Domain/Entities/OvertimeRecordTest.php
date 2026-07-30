<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entities;

use App\Organization\Domain\Entities\OvertimeRecord;
use App\Organization\Domain\Enums\OvertimeStatus;
use App\Organization\Domain\Events\OvertimeApproved;
use App\Organization\Domain\Events\OvertimeCorrected;
use App\Organization\Domain\Events\OvertimeRegistered;
use App\Organization\Domain\Events\OvertimeRejected;
use App\Organization\Domain\Events\OvertimeReviewRequested;
use App\Organization\Domain\Exceptions\OvertimeRecordException;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class OvertimeRecordTest extends TestCase
{
    private function createRecord(): OvertimeRecord
    {
        return OvertimeRecord::register(
            new OvertimeRecordIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2024-06-15'),
            new DateTimeImmutable('2024-06-15 18:00'),
            new DateTimeImmutable('2024-06-15 20:30'),
            'Mantenimiento de emergencia',
        );
    }

    public function testRegisterCreatesPendingRecord(): void
    {
        $record = $this->createRecord();

        self::assertSame(OvertimeStatus::PENDING, $record->status());
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $record->id());
        self::assertSame('01BXZ4MDEKTSV4RRFFQ69G5ABC', (string) $record->employeeId());
        self::assertSame('2024-06-15', $record->workedAt()->format('Y-m-d'));
        self::assertSame('18:00', $record->startTime()->format('H:i'));
        self::assertSame('20:30', $record->endTime()->format('H:i'));
        self::assertSame('Mantenimiento de emergencia', $record->reason());
        self::assertNull($record->evidenceUrl());
        self::assertNull($record->reviewedBy());
        self::assertNull($record->rejectionReason());
        self::assertNull($record->reviewedAt());
        self::assertNotNull($record->createdAt());
        self::assertNull($record->updatedAt());
    }

    public function testRegisterWithEvidenceUrl(): void
    {
        $record = OvertimeRecord::register(
            new OvertimeRecordIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2024-06-15'),
            new DateTimeImmutable('2024-06-15 18:00'),
            new DateTimeImmutable('2024-06-15 20:30'),
            'Mantenimiento',
            'https://evidencia.com/foto.jpg',
        );

        self::assertSame('https://evidencia.com/foto.jpg', $record->evidenceUrl());
    }

    public function testRegisterThrowsWhenWorkedAtInFuture(): void
    {
        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('fecha futura');

        OvertimeRecord::register(
            new OvertimeRecordIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('18:00'),
            new DateTimeImmutable('20:30'),
            'Mantenimiento',
        );
    }

    public function testRegisterThrowsWhenEndTimeBeforeStartTime(): void
    {
        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('La hora de fin debe ser posterior');

        OvertimeRecord::register(
            new OvertimeRecordIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2024-06-15'),
            new DateTimeImmutable('2024-06-15 18:00'),
            new DateTimeImmutable('2024-06-15 17:00'),
            'Mantenimiento',
        );
    }

    public function testRegisterThrowsWhenEndTimeEqualsStartTime(): void
    {
        $this->expectException(OvertimeRecordException::class);

        OvertimeRecord::register(
            new OvertimeRecordIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2024-06-15'),
            new DateTimeImmutable('2024-06-15 18:00'),
            new DateTimeImmutable('2024-06-15 18:00'),
            'Mantenimiento',
        );
    }

    public function testRegisterThrowsWhenReasonEmpty(): void
    {
        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('motivo es obligatorio');

        OvertimeRecord::register(
            new OvertimeRecordIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2024-06-15'),
            new DateTimeImmutable('2024-06-15 18:00'),
            new DateTimeImmutable('2024-06-15 20:30'),
            '  ',
        );
    }

    public function testRegisterRecordsOvertimeRegisteredEvent(): void
    {
        $record = $this->createRecord();
        $events = $record->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OvertimeRegistered::class, $events[0]);
    }

    // ── Request Review ──

    public function testRequestReviewTransitionsToInReview(): void
    {
        $record = $this->createRecord();
        $reviewer = new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF');

        $record->requestReview($reviewer);

        self::assertSame(OvertimeStatus::IN_REVIEW, $record->status());
        self::assertSame('01CYZ5NDEKTSV4RRFFQ69G5DEF', (string) $record->reviewedBy());
        self::assertNotNull($record->reviewedAt());
        self::assertNotNull($record->updatedAt());
    }

    public function testRequestReviewRecordsOvertimeReviewRequestedEvent(): void
    {
        $record = $this->createRecord();
        $record->pullDomainEvents(); // descartar evento de registro

        $record->requestReview(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'));
        $events = $record->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OvertimeReviewRequested::class, $events[0]);
    }

    public function testRequestReviewThrowsWhenNotPending(): void
    {
        $record = $this->createRecord();
        $record->approve(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'));

        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('registro pendiente');

        $record->requestReview(new EmployeeIdValueObject('01DEG6NDEKTSV4RRFFQ69G5GHB'));
    }

    // ── Correct ──

    public function testCorrectTransitionsBackToPending(): void
    {
        $record = $this->createRecord();
        $reviewer = new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF');
        $record->requestReview($reviewer);

        $record->correct(
            new DateTimeImmutable('2024-06-16'),
            new DateTimeImmutable('2024-06-16 19:00'),
            new DateTimeImmutable('2024-06-16 21:00'),
            'Motivo corregido',
        );

        self::assertSame(OvertimeStatus::PENDING, $record->status());
        self::assertNull($record->reviewedBy());
        self::assertNull($record->reviewedAt());
        self::assertSame('Motivo corregido', $record->reason());
    }

    public function testCorrectRecordsOvertimeCorrectedEvent(): void
    {
        $record = $this->createRecord();
        $record->pullDomainEvents();

        $record->requestReview(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'));
        $record->pullDomainEvents();

        $record->correct(
            new DateTimeImmutable('2024-06-16'),
            new DateTimeImmutable('2024-06-16 19:00'),
            new DateTimeImmutable('2024-06-16 21:00'),
            'Motivo corregido',
        );
        $events = $record->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OvertimeCorrected::class, $events[0]);
    }

    public function testCorrectThrowsWhenNotInReview(): void
    {
        $record = $this->createRecord();

        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('registro en revisión');

        $record->correct(
            new DateTimeImmutable('2024-06-16'),
            new DateTimeImmutable('2024-06-16 19:00'),
            new DateTimeImmutable('2024-06-16 21:00'),
            'Motivo corregido',
        );
    }

    public function testCorrectThrowsWhenWorkedAtInFuture(): void
    {
        $record = $this->createRecord();
        $record->requestReview(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'));

        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('fecha futura');

        $record->correct(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('18:00'),
            new DateTimeImmutable('20:30'),
            'Motivo',
        );
    }

    // ── Approve ──

    public function testApproveTransitionsToApproved(): void
    {
        $record = $this->createRecord();
        $reviewer = new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF');

        $record->approve($reviewer);

        self::assertSame(OvertimeStatus::APPROVED, $record->status());
        self::assertSame('01CYZ5NDEKTSV4RRFFQ69G5DEF', (string) $record->reviewedBy());
        self::assertNotNull($record->reviewedAt());
        self::assertNotNull($record->updatedAt());
    }

    public function testApproveRecordsOvertimeApprovedEvent(): void
    {
        $record = $this->createRecord();
        $record->pullDomainEvents();

        $record->approve(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'));
        $events = $record->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OvertimeApproved::class, $events[0]);
    }

    public function testApproveThrowsWhenNotPending(): void
    {
        $record = $this->createRecord();
        $record->approve(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'));

        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('Solo se puede aprobar un registro pendiente');

        $record->approve(new EmployeeIdValueObject('01DEG6NDEKTSV4RRFFQ69G5GHB'));
    }

    // ── Reject ──

    public function testRejectTransitionsToRejected(): void
    {
        $record = $this->createRecord();
        $reviewer = new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF');

        $record->reject($reviewer, 'Horas no autorizadas');

        self::assertSame(OvertimeStatus::REJECTED, $record->status());
        self::assertSame('Horas no autorizadas', $record->rejectionReason());
        self::assertNotNull($record->reviewedAt());
    }

    public function testRejectRecordsOvertimeRejectedEvent(): void
    {
        $record = $this->createRecord();
        $record->pullDomainEvents();

        $record->reject(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'), 'Horas no autorizadas');
        $events = $record->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OvertimeRejected::class, $events[0]);
    }

    public function testRejectThrowsWhenNotPending(): void
    {
        $record = $this->createRecord();
        $record->approve(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'));

        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('registro pendiente');

        $record->reject(new EmployeeIdValueObject('01DEG6NDEKTSV4RRFFQ69G5GHB'), 'Motivo');
    }

    public function testRejectThrowsWhenReasonEmpty(): void
    {
        $record = $this->createRecord();

        $this->expectException(OvertimeRecordException::class);
        $this->expectExceptionMessage('motivo de rechazo');

        $record->reject(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'), '');
    }

    public function testPullDomainEventsClearsEvents(): void
    {
        $record = $this->createRecord();
        $record->pullDomainEvents();

        $events = $record->pullDomainEvents();
        self::assertEmpty($events);
    }
}
