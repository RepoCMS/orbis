<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\ValueObjects\RmaRecordIdValueObject;

/**
 * Registro de envío a RMA (Return Merchandise Authorization).
 *
 * Cuando un repuesto falla y debe devolverse al fabricante para revisión,
 * se registra un RmaRecord con los datos del producto enviado.
 * El proceso lo realiza el coordinador de HW o jefe técnico externamente.
 */
final class RmaRecord
{
    private function __construct(
        private RmaRecordIdValueObject $id,
        private string                 $ticketId,
        private string                 $productCode,
        private ?string                $serialNumber,
        private int                    $quantity,
        private ?string                $notes,
        private string                 $createdBy,
        private \DateTimeImmutable     $createdAt,
        private ?\DateTimeImmutable    $deletedAt,
    ) {
    }

    public static function create(
        RmaRecordIdValueObject $id,
        string $ticketId,
        string $productCode,
        string $createdBy,
        ?string $serialNumber = null,
        int $quantity = 1,
        ?string $notes = null,
    ): self {
        if (trim($ticketId) === '') {
            throw new \InvalidArgumentException('El ID del ticket es obligatorio.');
        }
        if (trim($productCode) === '') {
            throw new \InvalidArgumentException('El código de producto es obligatorio.');
        }
        if (trim($createdBy) === '') {
            throw new \InvalidArgumentException('El usuario creador es obligatorio.');
        }
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1.');
        }

        return new self(
            id:           $id,
            ticketId:     trim($ticketId),
            productCode:  trim($productCode),
            serialNumber: $serialNumber !== null ? (trim($serialNumber) ?: null) : null,
            quantity:     $quantity,
            notes:        $notes !== null ? (trim($notes) ?: null) : null,
            createdBy:    trim($createdBy),
            createdAt:    new \DateTimeImmutable(),
            deletedAt:    null,
        );
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function id(): RmaRecordIdValueObject
    {
        return $this->id;
    }
    public function ticketId(): string
    {
        return $this->ticketId;
    }
    public function productCode(): string
    {
        return $this->productCode;
    }
    public function serialNumber(): ?string
    {
        return $this->serialNumber;
    }
    public function quantity(): int
    {
        return $this->quantity;
    }
    public function notes(): ?string
    {
        return $this->notes;
    }
    public function createdBy(): string
    {
        return $this->createdBy;
    }
    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
