<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

final readonly class TicketPendingSparePartResponse
{
    public function __construct(
        public string  $id,
        public string  $ticketId,
        public string  $productCode,
        public int     $quantity,
        public ?string $notes,
    ) {}

    public static function fromEntity(\App\Support\Domain\Entities\TicketPendingSparePart $pendingSparePart): self
    {
        return new self(
            id: (string) $pendingSparePart->id(),
            ticketId: (string) $pendingSparePart->ticketId(),
            productCode: $pendingSparePart->productCode(),
            quantity: $pendingSparePart->quantity(),
            notes: $pendingSparePart->notes(),
        );
    }
}
