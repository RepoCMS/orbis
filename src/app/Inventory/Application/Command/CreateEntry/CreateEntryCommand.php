<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateEntry;

final readonly class CreateEntryCommand
{
    public function __construct(
        public string $reference,
        public ?string $notes,
        public string $createdBy,
    ) {
    }
}
