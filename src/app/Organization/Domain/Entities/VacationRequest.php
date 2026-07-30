<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entities;

use App\Organization\Domain\Enums\VacationRequestStatus;
use App\Organization\Domain\Exceptions\VacationRequestException;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use DateTimeImmutable;

/**
 * Solicitud de vacaciones de un empleado.
 *
 * Flujo de vida:
 *   PENDING  → APPROVED  (supervisor aprueba → se crea EmployeeSpecialPeriod)
 *   PENDING  → REJECTED  (supervisor rechaza)
 *   PENDING  → CANCELLED (empleado cancela antes de aprobación)
 *
 * EmployeeSpecialPeriod se crea ÚNICAMENTE cuando la solicitud es APPROVED.
 */
final class VacationRequest
{
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;

    private function __construct(
        private VacationRequestIdValueObject $id,
        private EmployeeIdValueObject $employeeId,
        private DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate,
        private VacationRequestStatus $status = VacationRequestStatus::PENDING,
        private ?EmployeeIdValueObject $reviewedBy = null,
        private ?string $rejectionReason = null,
        private ?DateTimeImmutable $reviewedAt = null,
    ) {
        if ($endDate <= $startDate) {
            throw new VacationRequestException(
                'La fecha de inicio debe ser anterior a la fecha de fin.'
            );
        }

        $this->createdAt = new DateTimeImmutable();
    }

    public static function request(
        VacationRequestIdValueObject $id,
        EmployeeIdValueObject $employeeId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
    ): self {
        return new self($id, $employeeId, $startDate, $endDate);
    }

    /**
     * Aprueba la solicitud (PENDING → APPROVED).
     * EmployeeSpecialPeriod se creará en el handler.
     */
    public function approve(EmployeeIdValueObject $reviewedBy): void
    {
        if (!$this->status->isPending()) {
            throw new VacationRequestException(
                'Solo se puede aprobar una solicitud pendiente.'
            );
        }

        $this->status = VacationRequestStatus::APPROVED;
        $this->reviewedBy = $reviewedBy;
        $this->reviewedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Rechaza la solicitud (PENDING → REJECTED).
     */
    public function reject(EmployeeIdValueObject $reviewedBy, string $reason): void
    {
        if (!$this->status->isPending()) {
            throw new VacationRequestException(
                'Solo se puede rechazar una solicitud pendiente.'
            );
        }

        if ($reason === '') {
            throw new VacationRequestException('El motivo de rechazo no puede estar vacío.');
        }

        $this->status = VacationRequestStatus::REJECTED;
        $this->reviewedBy = $reviewedBy;
        $this->rejectionReason = $reason;
        $this->reviewedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Cancela la solicitud (PENDING → CANCELLED).
     */
    public function cancel(): void
    {
        if (!$this->status->isPending()) {
            throw new VacationRequestException(
                'Solo se puede cancelar una solicitud pendiente.'
            );
        }

        $this->status = VacationRequestStatus::CANCELLED;
        $this->updatedAt = new DateTimeImmutable();
    }

    // ── Getters ──────────────────────────────────────────────────────────────

    public function id(): VacationRequestIdValueObject
    {
        return $this->id;
    }

    public function employeeId(): EmployeeIdValueObject
    {
        return $this->employeeId;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function status(): VacationRequestStatus
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
