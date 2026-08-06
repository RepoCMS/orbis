<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\PhysicalCountStatus;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Conteo físico de inventario (stock-taking).
 *
 * Aggregate root que agrupa los ítems contados en una ubicación.
 * Ciclo: DRAFT → PENDING_APPROVAL → APPROVED / REJECTED.
 *
 * Al ser aprobado, el Application layer crea un Movement(ADJUSTMENT)
 * por cada ítem con diferencia distinta de cero.
 */
final class PhysicalCount
{
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $deletedAt = null;
    private ?DateTimeImmutable $approvedAt = null;
    private ?DateTimeImmutable $rejectedAt = null;
    private ?string $rejectionReason = null;
    private ?string $approvedBy = null;

    /** @var Collection<int, PhysicalCountItem> */
    private Collection $items;

    private function __construct(
        private PhysicalCountIdValueObject $id,
        private string $locationId,
        private string $countedBy,
        private ?string $notes,
        private PhysicalCountStatus $status,
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    /**
     * Inicia un nuevo conteo físico en estado DRAFT.
     */
    public static function start(
        PhysicalCountIdValueObject $id,
        string $locationId,
        string $countedBy,
        ?string $notes = null,
    ): self {
        return new self(
            id:         $id,
            locationId: $locationId,
            countedBy:  $countedBy,
            notes:      $notes,
            status:     PhysicalCountStatus::DRAFT,
        );
    }

    // ─── Getters ──────────────────────────────────────────────────────────────

    public function id(): PhysicalCountIdValueObject
    {
        return $this->id;
    }

    public function locationId(): string
    {
        return $this->locationId;
    }

    public function countedBy(): string
    {
        return $this->countedBy;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function status(): PhysicalCountStatus
    {
        return $this->status;
    }

    /** @return PhysicalCountItem[] */
    public function items(): array
    {
        return $this->items->toArray();
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

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function rejectedAt(): ?DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function approvedBy(): ?string
    {
        return $this->approvedBy;
    }

    // ─── State queries ────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status->isDraft();
    }

    public function isPendingApproval(): bool
    {
        return $this->status->isPendingApproval();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function isRejected(): bool
    {
        return $this->status->isRejected();
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    // ─── Item management ──────────────────────────────────────────────────────

    /**
     * Agrega un ítem al conteo. Solo disponible en estado DRAFT.
     */
    public function addItem(PhysicalCountItem $item): void
    {
        if (!$this->isDraft()) {
            throw new InventoryDomainException(
                'No se pueden agregar ítems a un conteo que no está en estado borrador.'
            );
        }

        $this->items->add($item);
    }

    // ─── Status transitions ────────────────────────────────────────────────────

    /**
     * Envía el conteo para aprobación.
     * Requiere: estado DRAFT y al menos un ítem.
     */
    public function submit(): void
    {
        if (!$this->isDraft()) {
            throw new InventoryDomainException(
                'Solo se pueden enviar conteos en estado borrador.'
            );
        }

        if ($this->items->isEmpty()) {
            throw new InventoryDomainException(
                'No se puede enviar un conteo sin ítems.'
            );
        }

        $this->status = PhysicalCountStatus::PENDING_APPROVAL;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Aprueba el conteo y genera ajustes de stock en el Application layer.
     * Requiere: estado PENDING_APPROVAL.
     */
    public function approve(string $approvedBy): void
    {
        if (!$this->isPendingApproval()) {
            throw new InventoryDomainException(
                'Solo se pueden aprobar conteos en estado pendiente de aprobación.'
            );
        }

        $this->status = PhysicalCountStatus::APPROVED;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Rechaza el conteo.
     * Requiere: estado PENDING_APPROVAL.
     */
    public function reject(string $reason): void
    {
        if (!$this->isPendingApproval()) {
            throw new InventoryDomainException(
                'Solo se pueden rechazar conteos en estado pendiente de aprobación.'
            );
        }

        if (trim($reason) === '') {
            throw new InventoryDomainException(
                'El motivo de rechazo no puede estar vacío.'
            );
        }

        $this->status = PhysicalCountStatus::REJECTED;
        $this->rejectionReason = $reason;
        $this->rejectedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Obtiene los ítems que tienen diferencia distinta de cero (necesitan ajuste).
     *
     * @return PhysicalCountItem[]
     */
    public function itemsNeedingAdjustment(): array
    {
        return array_values(
            array_filter(
                $this->items->toArray(),
                static fn (PhysicalCountItem $item) => $item->difference() !== 0,
            )
        );
    }

    // ─── Soft delete ──────────────────────────────────────────────────────────

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }
}
