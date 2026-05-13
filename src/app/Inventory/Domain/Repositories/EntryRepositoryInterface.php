<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;

interface EntryRepositoryInterface
{
    public function findById(EntryIdValueObject $id): ?Entry;

    public function findByReference(string $reference): ?Entry;

    /** @return Entry[] */
    public function findDrafts(): array;

    /** @return Entry[] */
    public function findAll(): array;

    public function save(Entry $entry): void;
}
