<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CompleteRepair;

final readonly class CompleteRepairCommand
{
    public function __construct(
        public string $id,
        public ?string $completionNotes,
    ) {
    }
}
