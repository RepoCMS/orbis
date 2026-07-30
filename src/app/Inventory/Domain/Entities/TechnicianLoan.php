<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\TechnicianLoanStatus;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use DateTimeImmutable;

/**
 * Préstamo de stock serializado entre técnicos.
 *
 * Un técnico (fromTechnician) presta un ítem de stock serializado a otro
 * técnico (toTechnician). Cuando se aprueba, se crea un Movement(LOAN) para
 * registrar el movimiento del inventario.
 *
 * Ciclo: PENDING_LOAN → APPROVED → (Movement LOAN creado)
 *                      → REJECTED
 *                      → CANCELLED
 */
final class TechnicianLoan
{
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $deletedAt = null;

    private ?string $approvedBy = null;
    private ?DateTimeImmutable $approvedAt = null;
    private ?string $rejectedBy = null;
    private ?DateTimeImmutable $rejectedAt = null;
    private ?string $rejectionReason = null;
    private ?string $cancelledBy = null;

    private function __construct(
        private TechnicianLoanIdValueObject $id,
        private string $serializedStockId,
        private string $fromTechnicianId,
        private string $toTechnicianId,
        private ?string $ticketId,
        private TechnicianLoanStatus $status,
        private ?string $notes,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    public static function request(
        TechnicianLoanIdValueObject $id,
        string $serializedStockId,
        string $fromTechnicianId,
        string $toTechnicianId,
        ?string $ticketId = null,
        ?string $notes = null,
    ): self {
        return new self(
            id:                 $id,
            serializedStockId:  $serializedStockId,
            fromTechnicianId:   $fromTechnicianId,
            toTechnicianId:     $toTechnicianId,
            ticketId:           $ticketId,
            status:             TechnicianLoanStatus::PENDING_LOAN,
            notes:              $notes,
        );
    }

    // ─── Getters ──────────────────────────────────────────────────────────────

    public function id(): TechnicianLoanIdValueObject
    {
        return $this->id;
    }

    public function serializedStockId(): string
    {
        return $this->serializedStockId;
    }

    public function fromTechnicianId(): string
    {
        return $this->fromTechnicianId;
    }

    public function toTechnicianId(): string
    {
        return $this->toTechnicianId;
    }

    public function ticketId(): ?string
    {
        return $this->ticketId;
    }

    public function status(): TechnicianLoanStatus
    {
        return $this->status;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function approvedBy(): ?string
    {
        return $this->approvedBy;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function rejectedBy(): ?string
    {
        return $this->rejectedBy;
    }

    public function rejectedAt(): ?DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function cancelledBy(): ?string
    {
        return $this->cancelledBy;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    // ─── State queries ────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === TechnicianLoanStatus::PENDING_LOAN;
    }

    public function isApproved(): bool
    {
        return $this->status === TechnicianLoanStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === TechnicianLoanStatus::REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === TechnicianLoanStatus::CANCELLED;
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    // ─── Mutations ────────────────────────────────────────────────────────────

    /**
     * Aprueba el préstamo. Solo se puede aprobar desde PENDING_LOAN.
     * El handler es responsable de crear el Movement(LOAN) correspondiente.
     */
    public function approve(string $approvedBy): void
    {
        if (!$this->isPending()) {
            throw new \LogicException('Solo se pueden aprobar préstamos en estado PENDING_LOAN.');
        }

        $this->status     = TechnicianLoanStatus::APPROVED;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new DateTimeImmutable();
        $this->updatedAt  = new DateTimeImmutable();
    }

    /**
     * Rechaza el préstamo. Solo se puede rechazar desde PENDING_LOAN.
     */
    public function reject(string $reason, ?string $rejectedBy = null): void
    {
        if (!$this->isPending()) {
            throw new \LogicException('Solo se pueden rechazar préstamos en estado PENDING_LOAN.');
        }

        $this->status          = TechnicianLoanStatus::REJECTED;
        $this->rejectedBy      = $rejectedBy;
        $this->rejectedAt      = new DateTimeImmutable();
        $this->rejectionReason = $reason;
        $this->updatedAt       = new DateTimeImmutable();
    }

    /**
     * Cancela el préstamo. Solo se puede cancelar desde PENDING_LOAN.
     */
    public function cancel(?string $cancelledBy = null): void
    {
        if (!$this->isPending()) {
            throw new \LogicException('Solo se pueden cancelar préstamos en estado PENDING_LOAN.');
        }

        $this->status      = TechnicianLoanStatus::CANCELLED;
        $this->cancelledBy = $cancelledBy;
        $this->updatedAt   = new DateTimeImmutable();
    }

    /**
     * Soft-delete del registro.
     */
    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }
}
