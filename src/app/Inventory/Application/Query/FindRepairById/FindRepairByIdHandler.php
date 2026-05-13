<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\FindRepairById;

use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Application\Query\Response\RepairResponse;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;

final readonly class FindRepairByIdHandler
{
    public function __construct(
        private RepairRepositoryInterface $repairRepository,
    ) {
    }

    public function __invoke(string $id): RepairResponse
    {
        $repair = $this->repairRepository->findById(new RepairIdValueObject($id))
            ?? throw RepairNotFoundException::withId($id);

        return RepairResponse::fromEntity($repair);
    }
}
