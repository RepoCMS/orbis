<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Enums\EntryStatus;

final readonly class EntryResponse
{
    public function __construct(
        public string $id,
        public string $reference,
        public string $status,
        public string $statusLabel,
        public ?string $notes,
        public string $createdBy,
        public string $createdAt,
        public ?string $confirmedAt,
    ) {
    }

    public static function fromEntity(Entry $entry): self
    {
        $statusLabel = match ($entry->status()) {
            EntryStatus::DRAFT     => 'Borrador',
            EntryStatus::CONFIRMED => 'Confirmada',
            EntryStatus::CANCELLED => 'Cancelada',
        };

        return new self(
            id:          (string) $entry->id(),
            reference:   $entry->reference(),
            status:      $entry->status()->value,
            statusLabel: $statusLabel,
            notes:       $entry->notes(),
            createdBy:   (string) $entry->createdBy(),
            createdAt:   $entry->createdAt()->format('Y-m-d H:i:s'),
            confirmedAt: $entry->confirmedAt()?->format('Y-m-d H:i:s'),
        );
    }
}
