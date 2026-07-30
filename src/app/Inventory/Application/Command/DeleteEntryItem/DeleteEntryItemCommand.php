<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DeleteEntryItem;

final readonly class DeleteEntryItemCommand
{
    public function __construct(
        public string $entryItemId,
    ) {
    }
}
