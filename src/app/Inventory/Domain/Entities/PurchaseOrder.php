<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Enums\PurchaseOrderStatus;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use DateTimeImmutable;

/**
 * Orden de compra de productos al proveedor.
 * Una PurchaseOrder agrupa los PurchaseOrderItems (líneas de producto).
 * El estado se actualiza a medida que los items se reciben en Entries.
 */
final class PurchaseOrder
{
    public function __construct(
        private PurchaseOrderIdValueObject $id,
        private string $reference,
        private PurchaseOrderStatus $status,
        private ?string $notes,
        private UserIdValueObject $createdBy,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        PurchaseOrderIdValueObject $id,
        string $reference,
        ?string $notes,
        UserIdValueObject $createdBy,
    ): self {
        return new self(
            id:        $id,
            reference: $reference,
            status:    PurchaseOrderStatus::OPEN,
            notes:     $notes,
            createdBy: $createdBy,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function id(): PurchaseOrderIdValueObject
    {
        return $this->id;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function status(): PurchaseOrderStatus
    {
        return $this->status;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function createdBy(): UserIdValueObject
    {
        return $this->createdBy;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markPartial(): void
    {
        $this->status = PurchaseOrderStatus::PARTIAL;
    }

    public function markCompleted(): void
    {
        $this->status = PurchaseOrderStatus::COMPLETED;
    }

    public function update(string $reference, ?string $notes): void
    {
        $this->reference = $reference;
        $this->notes     = $notes;
    }

    public function cancel(): void
    {
        $this->status = PurchaseOrderStatus::CANCELLED;
    }

    public function isOpen(): bool
    {
        return $this->status === PurchaseOrderStatus::OPEN;
    }

    public function isCompleted(): bool
    {
        return $this->status === PurchaseOrderStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === PurchaseOrderStatus::CANCELLED;
    }
}
