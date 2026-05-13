<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\StartRepair;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;

final readonly class StartRepairHandler
{
    public function __construct(
        private RepairRepositoryInterface $repairRepository,
    ) {
    }

    public function __invoke(StartRepairCommand $command): void
    {
        $id     = new RepairIdValueObject($command->id);
        $repair = $this->repairRepository->findById($id)
            ?? throw RepairNotFoundException::withId($command->id);

        $repair->start(new UserIdValueObject($command->technicianId));

        $this->repairRepository->save($repair);
    }
}
