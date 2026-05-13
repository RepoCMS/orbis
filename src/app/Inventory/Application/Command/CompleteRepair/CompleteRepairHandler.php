<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CompleteRepair;

use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;

final readonly class CompleteRepairHandler
{
    public function __construct(
        private RepairRepositoryInterface $repairRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
    ) {
    }

    public function __invoke(CompleteRepairCommand $command): void
    {
        $id     = new RepairIdValueObject($command->id);
        $repair = $this->repairRepository->findById($id)
            ?? throw RepairNotFoundException::withId($command->id);

        $repair->complete($command->completionNotes);
        $this->repairRepository->save($repair);

        $stock = $this->serializedStockRepository->findById($repair->serializedStockId());
        if ($stock !== null) {
            $stock->release();
            $this->serializedStockRepository->save($stock);
        }
    }
}
