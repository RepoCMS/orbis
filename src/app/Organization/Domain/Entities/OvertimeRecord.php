<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entities;

use App\Organization\Domain\Enums\OvertimeStatus;
use App\Organization\Domain\Events\OvertimeApproved;
use App\Organization\Domain\Events\OvertimeCorrected;
use App\Organization\Domain\Events\OvertimeRejected;
use App\Organization\Domain\Events\OvertimeRegistered;
use App\Organization\Domain\Events\OvertimeReviewRequested;
use App\Organization\Domain\Exceptions\OvertimeRecordException;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;

final class OvertimeRecord implements TimestampableInterface
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?string $evidenceUrl = null;
    private ?EmployeeIdValueObject $reviewedBy = null;
    private ?string $rejectionReason = null;
    private ?DateTimeImmutable $reviewedAt = null;

    private function __construct(
        private OvertimeRecordIdValueObject $id,
        private EmployeeIdValueObject $employeeId,
        private DateTimeImmutable $workedAt,
        private DateTimeImmutable $startTime,
        private DateTimeImmutable $endTime,
        private string $reason,
        private OvertimeStatus $status = OvertimeStatus::PENDING,
    ) {
        if ($endTime <= $startTime) {
            throw new OvertimeRecordException('La hora de fin debe ser posterior a la hora de inicio.');
        }

        if (trim($reason) === '') {
            throw new OvertimeRecordException('El motivo es obligatorio.');
        }

        $this->createdAt = new DateTimeImmutable();
    }

    public static function register(
        OvertimeRecordIdValueObject $id,
        EmployeeIdValueObject $employeeId,
        DateTimeImmutable $workedAt,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime,
        string $reason,
        ?string $evidenceUrl = null,
    ): self {
        if ($workedAt > new DateTimeImmutable()) {
            throw new OvertimeRecordException('No se pueden registrar horas extras para una fecha futura.');
        }

        $record = new self(
            id: $id,
            employeeId: $employeeId,
            workedAt: $workedAt,
            startTime: $startTime,
            endTime: $endTime,
            reason: $reason,
            status: OvertimeStatus::PENDING,
        );

        $record->evidenceUrl = $evidenceUrl;

        $totalHours = ($record->endTime->getTimestamp() - $record->startTime->getTimestamp()) / 3600;

        $record->recordEvent(new OvertimeRegistered(
            overtimeRecordId: (string) $id,
            employeeId: (string) $employeeId,
            workedAt: $workedAt->format('Y-m-d'),
            startTime: $startTime->format('H:i'),
            endTime: $endTime->format('H:i'),
            totalHours: $totalHours,
        ));

        return $record;
    }

    public function requestReview(EmployeeIdValueObject $reviewedBy, ?string $correctionHint = null): void
    {
        if (!$this->status->isPending()) {
            throw new OvertimeRecordException('Solo se puede solicitar corrección de un registro pendiente.');
        }

        $this->status = OvertimeStatus::IN_REVIEW;
        $this->reviewedBy = $reviewedBy;
        $this->reviewedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(new OvertimeReviewRequested(
            overtimeRecordId: (string) $this->id,
            employeeId: (string) $this->employeeId,
            reviewRequestedBy: (string) $reviewedBy,
            correctionHint: $correctionHint,
        ));
    }

    public function correct(
        DateTimeImmutable $workedAt,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime,
        string $reason,
        ?string $evidenceUrl = null,
    ): void {
        if (!$this->status->isInReview()) {
            throw new OvertimeRecordException('Solo se puede corregir un registro en revisión.');
        }

        if ($workedAt > new DateTimeImmutable()) {
            throw new OvertimeRecordException('No se pueden registrar horas extras para una fecha futura.');
        }

        if ($endTime <= $startTime) {
            throw new OvertimeRecordException('La hora de fin debe ser posterior a la hora de inicio.');
        }

        if (trim($reason) === '') {
            throw new OvertimeRecordException('El motivo es obligatorio.');
        }

        $this->workedAt = $workedAt;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->reason = $reason;
        $this->evidenceUrl = $evidenceUrl;
        $this->status = OvertimeStatus::PENDING;
        $this->reviewedBy = null;
        $this->reviewedAt = null;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(new OvertimeCorrected(
            overtimeRecordId: (string) $this->id,
            employeeId: (string) $this->employeeId,
            workedAt: $workedAt->format('Y-m-d'),
            startTime: $startTime->format('H:i'),
            endTime: $endTime->format('H:i'),
        ));
    }

    public function approve(EmployeeIdValueObject $reviewedBy): void
    {
        if (!$this->status->isPending()) {
            throw new OvertimeRecordException('Solo se puede aprobar un registro pendiente.');
        }

        $this->status = OvertimeStatus::APPROVED;
        $this->reviewedBy = $reviewedBy;
        $this->reviewedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();

        $totalHours = ($this->endTime->getTimestamp() - $this->startTime->getTimestamp()) / 3600;

        $this->recordEvent(new OvertimeApproved(
            overtimeRecordId: (string) $this->id,
            employeeId: (string) $this->employeeId,
            approvedBy: (string) $reviewedBy,
            totalHours: $totalHours,
        ));
    }

    public function reject(EmployeeIdValueObject $reviewedBy, string $reason): void
    {
        if (!$this->status->isPending()) {
            throw new OvertimeRecordException('Solo se puede rechazar un registro pendiente.');
        }

        if (trim($reason) === '') {
            throw new OvertimeRecordException('El motivo de rechazo no puede estar vacío.');
        }

        $this->status = OvertimeStatus::REJECTED;
        $this->reviewedBy = $reviewedBy;
        $this->rejectionReason = $reason;
        $this->reviewedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(new OvertimeRejected(
            overtimeRecordId: (string) $this->id,
            employeeId: (string) $this->employeeId,
            rejectedBy: (string) $reviewedBy,
            reason: $reason,
        ));
    }

    /** @return DomainEvent[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    // ── Getters ──────────────────────────────────────────────────────────────

    public function id(): OvertimeRecordIdValueObject
    {
        return $this->id;
    }

    public function employeeId(): EmployeeIdValueObject
    {
        return $this->employeeId;
    }

    public function workedAt(): DateTimeImmutable
    {
        return $this->workedAt;
    }

    public function startTime(): DateTimeImmutable
    {
        return $this->startTime;
    }

    public function endTime(): DateTimeImmutable
    {
        return $this->endTime;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function evidenceUrl(): ?string
    {
        return $this->evidenceUrl;
    }

    public function status(): OvertimeStatus
    {
        return $this->status;
    }

    public function reviewedBy(): ?EmployeeIdValueObject
    {
        return $this->reviewedBy;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function reviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
