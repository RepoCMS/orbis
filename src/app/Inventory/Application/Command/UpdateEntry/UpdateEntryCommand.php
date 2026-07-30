<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\UpdateEntry;

final readonly class UpdateEntryCommand
{
    public function __construct(
        public string $entryId,
        public string $reference,
        public ?string $notes,
        public ?string $totalCost,
    ) {
    }
}
