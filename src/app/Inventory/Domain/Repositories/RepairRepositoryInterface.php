<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;

interface RepairRepositoryInterface
{
    public function findById(RepairIdValueObject $id): ?Repair;

    /** @return Repair[] */
    public function findBySerializedStock(SerializedStockIdValueObject $stockId): array;

    /** @return Repair[] */
    public function findByStatus(RepairStatus $status): array;

    public function save(Repair $repair): void;
}
