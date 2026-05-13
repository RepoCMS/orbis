<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ConfirmEntry;

final readonly class ConfirmEntryCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
