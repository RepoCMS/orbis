<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPendingSparePartIdValueObject;

final class TicketPendingSparePart
{
    public function __construct(
        private TicketPendingSparePartIdValueObject $id,
        private TicketIdValueObject                 $ticketId,
        private string                              $productCode,
        private int                                 $quantity,
        private ?string                             $notes,
    ) {}

    public static function register(
        TicketPendingSparePartIdValueObject $id,
        TicketIdValueObject $ticketId,
        string $productCode,
        int $quantity,
        ?string $notes = null,
    ): self {
        if (trim($productCode) === '') {
            throw new \InvalidArgumentException('El código de producto es obligatorio.');
        }
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1.');
        }

        return new self($id, $ticketId, $productCode, $quantity, $notes);
    }

    public function id(): TicketPendingSparePartIdValueObject { return $this->id; }
    public function ticketId(): TicketIdValueObject           { return $this->ticketId; }
    public function productCode(): string                     { return $this->productCode; }
    public function quantity(): int                           { return $this->quantity; }
    public function notes(): ?string                          { return $this->notes; }
}
