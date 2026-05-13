<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\SerialHistory;
use App\Inventory\Domain\ValueObjects\SerialHistoryIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;

interface SerialHistoryRepositoryInterface
{
    public function findById(SerialHistoryIdValueObject $id): ?SerialHistory;

    /** @return SerialHistory[] Ordered by changedAt ASC */
    public function findBySerializedStock(SerializedStockIdValueObject $serializedStockId): array;

    public function save(SerialHistory $history): void;
}
