<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Exceptions\InsufficientStockException;
use App\Inventory\Domain\Exceptions\StockReservedException;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use DateTimeImmutable;

/**
 * Lote de un producto fungible (sin numero de serie) en una ubicacion con un estado concreto.
 * Unicidad: (productId, locationId, status).
 *
 * Ejemplos de registros coexistentes para el mismo producto+ubicacion:
 *   - qty=7  status=AVAILABLE      -> disponibles para mover
 *   - qty=3  status=RESERVED       -> bloqueadas para un movimiento activo
 *   - qty=2  status=DAMAGED        -> danadas, no disponibles
 *
 * La capa de Aplicacion es responsable de dividir registros cuando se reserva
 * una cantidad parcial (crear RESERVED + reducir AVAILABLE).
 */
final class NonserializedStock
{
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        private NonserializedStockIdValueObject $id,
        private ProductIdValueObject $productId,
        private LocationIdValueObject $locationId,
        private int $quantity,
        private NonserializedStockStatus $status,
    ) {
    }

    public static function initialize(
        NonserializedStockIdValueObject $id,
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
        int $initialQuantity = 0,
    ): self {
        return new self($id, $productId, $locationId, $initialQuantity, NonserializedStockStatus::AVAILABLE);
    }

    // ─── Getters ─────────────────────────────────────────────────────────────

    public function id(): NonserializedStockIdValueObject
    {
        return $this->id;
    }

    public function productId(): ProductIdValueObject
    {
        return $this->productId;
    }

    public function locationId(): LocationIdValueObject
    {
        return $this->locationId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function status(): NonserializedStockStatus
    {
        return $this->status;
    }

    // ─── State helpers ───────────────────────────────────────────────────────

    public function isAvailable(): bool
    {
        return $this->status === NonserializedStockStatus::AVAILABLE;
    }

    public function isReserved(): bool
    {
        return $this->status === NonserializedStockStatus::RESERVED;
    }

    public function isDamaged(): bool
    {
        return $this->status === NonserializedStockStatus::DAMAGED;
    }

    public function isDecommissioned(): bool
    {
        return $this->status === NonserializedStockStatus::DECOMMISSIONED;
    }

    // ─── Commands ────────────────────────────────────────────────────────────

    /**
     * Agrega stock a un lote AVAILABLE.
     */
    public function addStock(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('La cantidad a agregar debe ser mayor a cero.');
        }

        if (!$this->isAvailable()) {
            throw new StockReservedException(
                sprintf('No se puede agregar stock a un lote con estado "%s".', $this->status->value)
            );
        }

        $this->quantity += $amount;
    }

    /**
     * Reduce la cantidad del lote (salida real de inventario).
     * Aplica a registros AVAILABLE o RESERVED.
     */
    public function reduce(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('La cantidad a reducir debe ser mayor a cero.');
        }

        if ($amount > $this->quantity) {
            throw new InsufficientStockException(
                sprintf(
                    'Stock insuficiente. Disponible: %d, Solicitado: %d.',
                    $this->quantity,
                    $amount
                )
            );
        }

        $this->quantity -= $amount;

        if ($this->quantity === 0) {
            $this->deletedAt = new DateTimeImmutable();
        }
    }

    /**
     * Marca el lote como RESERVED (bloqueado para un movimiento).
     * Solo se puede reservar un lote AVAILABLE.
     */
    public function reserve(): void
    {
        if (!$this->isAvailable()) {
            throw new StockReservedException(
                sprintf('No se puede reservar un lote con estado "%s".', $this->status->value)
            );
        }

        $this->status = NonserializedStockStatus::RESERVED;
    }

    /**
     * Libera la reserva (movimiento cancelado). Regresa a AVAILABLE.
     */
    public function release(): void
    {
        if (!$this->isReserved()) {
            throw new StockReservedException(
                sprintf('Solo se puede liberar un lote RESERVED, estado actual: "%s".', $this->status->value)
            );
        }

        $this->status = NonserializedStockStatus::AVAILABLE;
    }

    /**
     * Marca el lote como danado (fuera de servicio, pendiente de reparacion o baja).
     */
    public function markDamaged(): void
    {
        $this->status = NonserializedStockStatus::DAMAGED;
    }

    /**
     * Da de baja el lote definitivamente.
     */
    public function decommission(): void
    {
        $this->status = NonserializedStockStatus::DECOMMISSIONED;
    }

    /**
     * Restaura un lote DAMAGED a AVAILABLE tras reparacion.
     */
    public function restore(): void
    {
        if (!$this->isDamaged()) {
            throw new StockReservedException(
                sprintf('Solo se puede restaurar un lote DAMAGED, estado actual: "%s".', $this->status->value)
            );
        }

        $this->status = NonserializedStockStatus::AVAILABLE;
    }

    // ─── Soft delete ─────────────────────────────────────────────────────────

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }
}
