<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use DateTimeImmutable;

/**
 * Registro de un movimiento de inventario.
 *
 * Un Movement describe la intención de mover stock de una ubicación a otra.
 * El Application layer es responsable de coordinar los cambios en el stock
 * (reserve/consumeReserved) al crear/completar/cancelar movimientos.
 *
 * Para stock serializado: quantity = 1, serializedStockId = ID del ítem movido.
 * Para stock no serializado: quantity > 1 posible, serializedStockId = null.
 */
final class Movement
{
    private ?DateTimeImmutable $completedAt = null;

    public function __construct(
        private MovementIdValueObject $id,
        private ProductIdValueObject $productId,
        private MovementType $type,
        private ?LocationIdValueObject $sourceLocationId,
        private ?LocationIdValueObject $destinationLocationId,
        private ?SerializedStockIdValueObject $serializedStockId,
        private int $quantity,
        private MovementStatus $status,
        private ?string $notes,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        MovementIdValueObject $id,
        ProductIdValueObject $productId,
        MovementType $type,
        ?LocationIdValueObject $sourceLocationId,
        ?LocationIdValueObject $destinationLocationId,
        ?SerializedStockIdValueObject $serializedStockId,
        int $quantity,
        ?string $notes = null,
    ): self {
        return new self(
            id:                   $id,
            productId:            $productId,
            type:                 $type,
            sourceLocationId:     $sourceLocationId,
            destinationLocationId: $destinationLocationId,
            serializedStockId:    $serializedStockId,
            quantity:             $quantity,
            status:               MovementStatus::PENDING,
            notes:                $notes,
            createdAt:            new DateTimeImmutable(),
        );
    }

    public function id(): MovementIdValueObject
    {
        return $this->id;
    }

    public function productId(): ProductIdValueObject
    {
        return $this->productId;
    }

    public function type(): MovementType
    {
        return $this->type;
    }

    public function sourceLocationId(): ?LocationIdValueObject
    {
        return $this->sourceLocationId;
    }

    public function destinationLocationId(): ?LocationIdValueObject
    {
        return $this->destinationLocationId;
    }

    public function serializedStockId(): ?SerializedStockIdValueObject
    {
        return $this->serializedStockId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function status(): MovementStatus
    {
        return $this->status;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function isPending(): bool
    {
        return $this->status === MovementStatus::PENDING;
    }

    public function isInTransit(): bool
    {
        return $this->status === MovementStatus::IN_TRANSIT;
    }

    public function isCompleted(): bool
    {
        return $this->status === MovementStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === MovementStatus::CANCELLED;
    }

    public function startTransit(): void
    {
        $this->status = MovementStatus::IN_TRANSIT;
    }

    public function complete(): void
    {
        $this->status      = MovementStatus::COMPLETED;
        $this->completedAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        $this->status = MovementStatus::CANCELLED;
    }
}
