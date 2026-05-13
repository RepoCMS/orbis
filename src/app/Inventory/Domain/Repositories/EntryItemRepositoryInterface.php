<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\EntryItem;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Inventory\Domain\ValueObjects\EntryItemIdValueObject;

interface EntryItemRepositoryInterface
{
    public function findById(EntryItemIdValueObject $id): ?EntryItem;

    /** @return EntryItem[] */
    public function findByEntry(EntryIdValueObject $entryId): array;

    public function save(EntryItem $item): void;
}
