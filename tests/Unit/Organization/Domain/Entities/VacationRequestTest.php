<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entities;

use App\Organization\Domain\Entities\VacationRequest;
use App\Organization\Domain\Enums\VacationRequestStatus;
use App\Organization\Domain\Exceptions\VacationRequestException;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VacationRequestTest extends TestCase
{
    private function createRequest(): VacationRequest
    {
        return VacationRequest::request(
            new VacationRequestIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-07-15'),
        );
    }

    public function testRequestCreatesPendingRequest(): void
    {
        $request = $this->createRequest();

        self::assertSame(VacationRequestStatus::PENDING, $request->status());
        self::assertTrue($request->status()->isPending());
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $request->id());
        self::assertSame('01BXZ4MDEKTSV4RRFFQ69G5ABC', (string) $request->employeeId());
        self::assertSame('2024-07-01', $request->startDate()->format('Y-m-d'));
        self::assertSame('2024-07-15', $request->endDate()->format('Y-m-d'));
        self::assertNull($request->reviewedBy());
        self::assertNull($request->rejectionReason());
        self::assertNull($request->reviewedAt());
        self::assertNotNull($request->createdAt());
        self::assertNull($request->updatedAt());
    }

    public function testRequestThrowsWhenEndDateNotAfterStartDate(): void
    {
        $this->expectException(VacationRequestException::class);
        $this->expectExceptionMessage('La fecha de inicio debe ser anterior');

        VacationRequest::request(
            new VacationRequestIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2024-07-15'),
            new DateTimeImmutable('2024-07-15'),
        );
    }

    public function testRequestThrowsWhenEndDateBeforeStartDate(): void
    {
        $this->expectException(VacationRequestException::class);

        VacationRequest::request(
            new VacationRequestIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2024-07-15'),
            new DateTimeImmutable('2024-07-01'),
        );
    }

    // ── Approve ──

    public function testApproveTransitionsToApproved(): void
    {
        $request = $this->createRequest();
        $reviewer = new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF');

        $request->approve($reviewer);

        self::assertSame(VacationRequestStatus::APPROVED, $request->status());
        self::assertSame('01CYZ5NDEKTSV4RRFFQ69G5DEF', (string) $request->reviewedBy());
        self::assertNotNull($request->reviewedAt());
        self::assertNotNull($request->updatedAt());
    }

    public function testApproveThrowsWhenNotPending(): void
    {
        $request = $this->createRequest();
        $request->approve(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'));

        $this->expectException(VacationRequestException::class);
        $this->expectExceptionMessage('Solo se puede aprobar una solicitud pendiente');

        $request->approve(new EmployeeIdValueObject('01DEG6NDEKTSV4RRFFQ69G5GHB'));
    }

    // ── Reject ──

    public function testRejectTransitionsToRejected(): void
    {
        $request = $this->createRequest();
        $reviewer = new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF');

        $request->reject($reviewer, 'No hay cobertura');

        self::assertSame(VacationRequestStatus::REJECTED, $request->status());
        self::assertSame('01CYZ5NDEKTSV4RRFFQ69G5DEF', (string) $request->reviewedBy());
        self::assertSame('No hay cobertura', $request->rejectionReason());
        self::assertNotNull($request->reviewedAt());
        self::assertNotNull($request->updatedAt());
    }

    public function testRejectThrowsWhenNotPending(): void
    {
        $request = $this->createRequest();
        $request->approve(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'));

        $this->expectException(VacationRequestException::class);
        $this->expectExceptionMessage('Solo se puede rechazar una solicitud pendiente');

        $request->reject(new EmployeeIdValueObject('01DEG6NDEKTSV4RRFFQ69G5GHB'), 'Motivo');
    }

    public function testRejectThrowsWhenReasonEmpty(): void
    {
        $request = $this->createRequest();

        $this->expectException(VacationRequestException::class);
        $this->expectExceptionMessage('motivo de rechazo');

        $request->reject(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'), '');
    }

    // ── Cancel ──

    public function testCancelTransitionsToCancelled(): void
    {
        $request = $this->createRequest();

        $request->cancel();

        self::assertSame(VacationRequestStatus::CANCELLED, $request->status());
        self::assertNotNull($request->updatedAt());
    }

    public function testCancelThrowsWhenNotPending(): void
    {
        $request = $this->createRequest();
        $request->approve(new EmployeeIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'));

        $this->expectException(VacationRequestException::class);
        $this->expectExceptionMessage('Solo se puede cancelar una solicitud pendiente');

        $request->cancel();
    }
}
