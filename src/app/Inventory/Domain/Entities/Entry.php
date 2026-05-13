<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Enums\EntryStatus;
use App\Inventory\Domain\Exceptions\EntryAlreadyConfirmedException;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use DateTimeImmutable;

/**
 * Entrada de inventario: registro de recepción de mercancía.
 *
 * Un Entry agrupa los EntryItems (ítems recibidos). Puede referenciar
 * ítems de múltiples PurchaseOrders. Al confirmarse, se carga el stock
 * correspondiente al inventario (SerializedStock / NonserializedStock).
 */
final class Entry
{
    public function __construct(
        private EntryIdValueObject $id,
        private string $reference,
        private EntryStatus $status,
        private ?string $notes,
        private UserIdValueObject $createdBy,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $confirmedAt,
    ) {
    }

    public static function create(
        EntryIdValueObject $id,
        string $reference,
        ?string $notes,
        UserIdValueObject $createdBy,
    ): self {
        return new self(
            id:          $id,
            reference:   $reference,
            status:      EntryStatus::DRAFT,
            notes:       $notes,
            createdBy:   $createdBy,
            createdAt:   new DateTimeImmutable(),
            confirmedAt: null,
        );
    }

    public function id(): EntryIdValueObject
    {
        return $this->id;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function status(): EntryStatus
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

    public function confirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function isDraft(): bool
    {
        return $this->status === EntryStatus::DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === EntryStatus::CONFIRMED;
    }

    /**
     * Confirma la entrada y carga el stock al inventario.
     * Solo puede confirmarse una vez.
     */
    public function confirm(): void
    {
        if (!$this->isDraft()) {
            throw new EntryAlreadyConfirmedException(
                sprintf('La entrada "%s" ya fue %s y no puede modificarse.', $this->reference, $this->status->value)
            );
        }

        $this->status      = EntryStatus::CONFIRMED;
        $this->confirmedAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        if (!$this->isDraft()) {
            throw new EntryAlreadyConfirmedException(
                sprintf('La entrada "%s" ya fue %s y no puede cancelarse.', $this->reference, $this->status->value)
            );
        }

        $this->status = EntryStatus::CANCELLED;
    }
}
