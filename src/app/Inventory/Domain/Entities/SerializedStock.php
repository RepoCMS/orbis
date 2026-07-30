<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\SerializedStockStatus;
use App\Inventory\Domain\Exceptions\StockReservedException;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use DateTimeImmutable;

/**
 * Unidad física de inventario identificable por número de serie.
 * Cada registro representa exactamente una pieza o repuesto.
 */
final class SerializedStock
{
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $deletedAt = null;
    private ?string $createdBy = null;
    private ?string $updatedBy = null;
    private ?string $deletedBy = null;

    // Origin tracking (US16e)
    private ?string $originTicketId = null;
    private ?string $originClientId = null;
    private ?string $originTerminalId = null;
    private bool $isVandalism = false;
    private int $repairCount = 0;

    private function __construct(
        private SerializedStockIdValueObject $id,
        private ProductIdValueObject $productId,
        private StockSerialNumberValueObject $serialNumber,
        private LocationIdValueObject $locationId,
        private SerializedStockStatus $status,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    public static function register(
        SerializedStockIdValueObject $id,
        ProductIdValueObject $productId,
        StockSerialNumberValueObject $serialNumber,
        LocationIdValueObject $locationId,
        ?string $originTicketId = null,
        ?string $originClientId = null,
        ?string $originTerminalId = null,
        bool $isVandalism = false,
    ): self {
        $stock = new self($id, $productId, $serialNumber, $locationId, SerializedStockStatus::AVAILABLE);
        $stock->originTicketId   = $originTicketId;
        $stock->originClientId   = $originClientId;
        $stock->originTerminalId = $originTerminalId;
        $stock->isVandalism      = $isVandalism;

        return $stock;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function id(): SerializedStockIdValueObject
    {
        return $this->id;
    }

    public function productId(): ProductIdValueObject
    {
        return $this->productId;
    }

    public function serialNumber(): StockSerialNumberValueObject
    {
        return $this->serialNumber;
    }

    public function locationId(): LocationIdValueObject
    {
        return $this->locationId;
    }

    public function status(): SerializedStockStatus
    {
        return $this->status;
    }

    public function isAvailable(): bool
    {
        return $this->status === SerializedStockStatus::AVAILABLE;
    }

    public function isReserved(): bool
    {
        return $this->status === SerializedStockStatus::RESERVED;
    }

    // ─── Origin tracking (US16e) ──────────────────────────────────────────────

    public function originTicketId(): ?string
    {
        return $this->originTicketId;
    }

    public function originClientId(): ?string
    {
        return $this->originClientId;
    }

    public function originTerminalId(): ?string
    {
        return $this->originTerminalId;
    }

    public function isVandalism(): bool
    {
        return $this->isVandalism;
    }

    public function repairCount(): int
    {
        return $this->repairCount;
    }

    public function incrementRepairCount(): void
    {
        $this->repairCount++;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsVandalism(): void
    {
        $this->isVandalism = true;
    }

    public function createdBy(): ?string
    {
        return $this->createdBy;
    }

    public function updatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function deletedBy(): ?string
    {
        return $this->deletedBy;
    }

    public function setCreatedBy(string $userId): void
    {
        $this->createdBy = $userId;
    }

    public function setUpdatedBy(string $userId): void
    {
        $this->updatedBy = $userId;
        $this->updatedAt = new DateTimeImmutable();
    }

    // ─── State mutators ───────────────────────────────────────────────────

    /**
     * Reserva el ítem para un movimiento pendiente.
     * Solo se puede reservar si está AVAILABLE.
     */
    public function reserve(): void
    {
        if (!$this->isAvailable()) {
            throw new StockReservedException(
                sprintf(
                    'El ítem con serie "%s" no está disponible (estado: %s).',
                    $this->serialNumber,
                    $this->status->value
                )
            );
        }

        $this->status   = SerializedStockStatus::RESERVED;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Libera la reserva (movimiento cancelado).
     */
    public function release(): void
    {
        $this->status    = SerializedStockStatus::AVAILABLE;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Completa el traslado: actualiza la ubicación y vuelve a AVAILABLE.
     */
    public function completeTransfer(LocationIdValueObject $destination): void
    {
        $this->locationId = $destination;
        $this->status     = SerializedStockStatus::AVAILABLE;
        $this->updatedAt  = new DateTimeImmutable();
    }

    public function markDamaged(): void
    {
        $this->status    = SerializedStockStatus::DAMAGED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markSuspect(): void
    {
        $this->status    = SerializedStockStatus::SUSPECT;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function decommission(): void
    {
        $this->status    = SerializedStockStatus::DECOMMISSIONED;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Aplica una transformación de laboratorio: cambia producto y/o número de serie.
     */
    public function transform(
        ?ProductIdValueObject $newProductId,
        ?StockSerialNumberValueObject $newSerialNumber,
    ): void {
        if ($newProductId !== null) {
            $this->productId = $newProductId;
        }
        if ($newSerialNumber !== null) {
            $this->serialNumber = $newSerialNumber;
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function delete(?string $deletedBy = null): void
    {
        $this->deletedAt = new DateTimeImmutable();
        $this->deletedBy = $deletedBy;
        $this->updatedAt = new DateTimeImmutable();
    }
}
