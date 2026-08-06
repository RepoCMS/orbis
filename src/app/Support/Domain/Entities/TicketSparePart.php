<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketSparePartIdValueObject;

final class TicketSparePart
{
    public function __construct(
        private TicketSparePartIdValueObject $id,
        private TicketIdValueObject          $ticketId,
        private TicketFindingIdValueObject   $findingId,
        private string                       $productCode,
        private ?string                      $serialNumber,
        private int                          $quantity,
        private bool                         $isIncoming,
        private bool                         $isVisibleToClient,
        private bool                         $autoMarkedDamaged,
    ) {
    }

    public static function register(
        TicketSparePartIdValueObject $id,
        TicketIdValueObject $ticketId,
        TicketFindingIdValueObject $findingId,
        string $productCode,
        ?string $serialNumber,
        int $quantity,
        bool $isIncoming,
        bool $isVisibleToClient = true,
    ): self {
        if (trim($productCode) === '') {
            throw new \InvalidArgumentException('El código de producto es obligatorio.');
        }
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1.');
        }
        if ($serialNumber !== null && $quantity !== 1) {
            throw new \InvalidArgumentException('Un repuesto con serial siempre tiene cantidad 1.');
        }

        return new self(
            id: $id,
            ticketId: $ticketId,
            findingId: $findingId,
            productCode: $productCode,
            serialNumber: $serialNumber,
            quantity: $quantity,
            isIncoming: $isIncoming,
            isVisibleToClient: $isVisibleToClient,
            autoMarkedDamaged: false,
        );
    }

    public function markAutoMarkedDamaged(): void
    {
        $this->autoMarkedDamaged = true;
    }

    public function setVisibleToClient(bool $visible): void
    {
        $this->isVisibleToClient = $visible;
    }

    public function id(): TicketSparePartIdValueObject
    {
        return $this->id;
    }
    public function ticketId(): TicketIdValueObject
    {
        return $this->ticketId;
    }
    public function findingId(): TicketFindingIdValueObject
    {
        return $this->findingId;
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
    public function isIncoming(): bool
    {
        return $this->isIncoming;
    }
    public function isVisibleToClient(): bool
    {
        return $this->isVisibleToClient;
    }
    public function autoMarkedDamaged(): bool
    {
        return $this->autoMarkedDamaged;
    }
}
