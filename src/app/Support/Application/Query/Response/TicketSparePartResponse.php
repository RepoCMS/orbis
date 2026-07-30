<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

final readonly class TicketSparePartResponse
{
    public function __construct(
        public string  $id,
        public string  $ticketId,
        public string  $findingId,
        public string  $productCode,
        public ?string $serialNumber,
        public int     $quantity,
        public bool    $isIncoming,
        public bool    $isVisibleToClient,
        public bool    $autoMarkedDamaged,
    ) {}

    public static function fromEntity(\App\Support\Domain\Entities\TicketSparePart $sparePart): self
    {
        return new self(
            id: (string) $sparePart->id(),
            ticketId: (string) $sparePart->ticketId(),
            findingId: (string) $sparePart->findingId(),
            productCode: $sparePart->productCode(),
            serialNumber: $sparePart->serialNumber(),
            quantity: $sparePart->quantity(),
            isIncoming: $sparePart->isIncoming(),
            isVisibleToClient: $sparePart->isVisibleToClient(),
            autoMarkedDamaged: $sparePart->autoMarkedDamaged(),
        );
    }
}
