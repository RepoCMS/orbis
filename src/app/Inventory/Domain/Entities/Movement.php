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
    private ?string $createdByUserId = null;
    private ?string $assignedToUserId = null;
    private ?string $cancelledByUserId = null;
    private ?string $confirmedByUserId = null;
    private ?string $rejectedByUserId = null;
    private ?string $rejectionReason = null;
    private ?DateTimeImmutable $cancelledAt = null;
    private ?DateTimeImmutable $rejectedAt = null;
    private int $rejectedQuantity = 0;

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
        ?string $createdByUserId = null,
        ?string $assignedToUserId = null,
    ): self {
        $movement = new self(
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
        $movement->createdByUserId = $createdByUserId;
        $movement->assignedToUserId = $assignedToUserId;
        return $movement;
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

    public function createdByUserId(): ?string
    {
        return $this->createdByUserId;
    }

    public function assignedToUserId(): ?string
    {
        return $this->assignedToUserId;
    }

    public function confirmedByUserId(): ?string
    {
        return $this->confirmedByUserId;
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

    public function confirmBy(string $userId): void
    {
        $this->status = MovementStatus::COMPLETED;
        $this->completedAt = new DateTimeImmutable();
        $this->confirmedByUserId = $userId;
    }

    public function isRejected(): bool
    {
        return $this->status === MovementStatus::REJECTED;
    }

    public function cancel(): void
    {
        $this->status = MovementStatus::CANCELLED;
    }

    public function cancelBy(string $userId): void
    {
        $this->status          = MovementStatus::CANCELLED;
        $this->cancelledByUserId = $userId;
        $this->cancelledAt     = new DateTimeImmutable();
    }

    public function rejectBy(string $userId, string $reason): void
    {
        $this->status          = MovementStatus::REJECTED;
        $this->rejectedByUserId = $userId;
        $this->rejectionReason = $reason;
        $this->rejectedAt      = new DateTimeImmutable();
    }

    public function recordPartialRejection(string $userId, string $notes, int $rejectQty): void
    {
        $this->notes            = $notes;
        $this->rejectedByUserId = $userId;
        $this->rejectedQuantity += $rejectQty;
        $this->rejectedAt       = new DateTimeImmutable();
    }

    public function cancelledByUserId(): ?string
    {
        return $this->cancelledByUserId;
    }

    public function cancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function rejectedByUserId(): ?string
    {
        return $this->rejectedByUserId;
    }

    public function rejectedAt(): ?DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function rejectedQuantity(): int
    {
        return $this->rejectedQuantity;
    }
}
