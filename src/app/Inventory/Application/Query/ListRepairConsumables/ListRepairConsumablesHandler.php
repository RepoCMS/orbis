<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListRepairConsumables;

use App\Inventory\Application\Query\Response\ConsumableResponse;
use App\Inventory\Domain\Repositories\ConsumableRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;

final readonly class ListRepairConsumablesHandler
{
    public function __construct(
        private ConsumableRepositoryInterface $consumableRepository,
    ) {
    }

    /** @return ConsumableResponse[] */
    public function __invoke(ListRepairConsumablesQuery $query): array
    {
        $repairId     = new RepairIdValueObject($query->repairId);
        $consumables  = $this->consumableRepository->findByRepairId($repairId);

        return array_map(
            static fn ($consumable) => ConsumableResponse::fromEntity($consumable),
            $consumables,
        );
    }
}
