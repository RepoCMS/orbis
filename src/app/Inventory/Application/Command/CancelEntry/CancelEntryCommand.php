<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelEntry;

final readonly class CancelEntryCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
