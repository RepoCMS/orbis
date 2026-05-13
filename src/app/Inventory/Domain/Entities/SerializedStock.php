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
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        private SerializedStockIdValueObject $id,
        private ProductIdValueObject $productId,
        private StockSerialNumberValueObject $serialNumber,
        private LocationIdValueObject $locationId,
        private SerializedStockStatus $status,
    ) {
    }

    public static function register(
        SerializedStockIdValueObject $id,
        ProductIdValueObject $productId,
        StockSerialNumberValueObject $serialNumber,
        LocationIdValueObject $locationId,
    ): self {
        return new self($id, $productId, $serialNumber, $locationId, SerializedStockStatus::AVAILABLE);
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

        $this->status = SerializedStockStatus::RESERVED;
    }

    /**
     * Libera la reserva (movimiento cancelado).
     */
    public function release(): void
    {
        $this->status = SerializedStockStatus::AVAILABLE;
    }

    /**
     * Completa el traslado: actualiza la ubicación y vuelve a AVAILABLE.
     */
    public function completeTransfer(LocationIdValueObject $destination): void
    {
        $this->locationId = $destination;
        $this->status     = SerializedStockStatus::AVAILABLE;
    }

    public function markDamaged(): void
    {
        $this->status = SerializedStockStatus::DAMAGED;
    }

    public function decommission(): void
    {
        $this->status = SerializedStockStatus::DECOMMISSIONED;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
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
    }
}
