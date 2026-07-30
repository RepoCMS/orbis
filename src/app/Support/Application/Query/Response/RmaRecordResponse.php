<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

use App\Support\Domain\Entities\RmaRecord;

final readonly class RmaRecordResponse
{
    public function __construct(
        public string $id,
        public string $ticketId,
        public string $productCode,
        public ?string $serialNumber,
        public int    $quantity,
        public ?string $notes,
        public string $createdBy,
        public string $createdAt,
    ) {}

    public static function fromEntity(RmaRecord $record): self
    {
        return new self(
            id:           (string) $record->id(),
            ticketId:     $record->ticketId(),
            productCode:  $record->productCode(),
            serialNumber: $record->serialNumber(),
            quantity:     $record->quantity(),
            notes:        $record->notes(),
            createdBy:    $record->createdBy(),
            createdAt:    $record->createdAt()->format('Y-m-d H:i:s'),
        );
    }
}
