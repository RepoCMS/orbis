<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\Consumable;
use App\Inventory\Domain\ValueObjects\ConsumableIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;

interface ConsumableRepositoryInterface
{
    public function findById(ConsumableIdValueObject $id): ?Consumable;

    /** @return Consumable[] */
    public function findByRepairId(RepairIdValueObject $repairId): array;

    public function save(Consumable $consumable): void;

    public function delete(Consumable $consumable): void;
}
