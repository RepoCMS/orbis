<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AssignRepair;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;

final readonly class AssignRepairHandler
{
    public function __construct(
        private RepairRepositoryInterface $repairRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private LocationRepositoryInterface $locationRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(AssignRepairCommand $command): void
    {
        $stockId = new SerializedStockIdValueObject($command->serializedStockId);
        $stock   = $this->serializedStockRepository->findById($stockId)
            ?? throw new ApplicationException(sprintf('Stock serializado con ID "%s" no encontrado.', $command->serializedStockId));

        $labId = new LocationIdValueObject($command->laboratoryId);
        $lab   = $this->locationRepository->findById($labId)
            ?? throw LocationNotFoundException::withId($command->laboratoryId);

        if (!$lab->isLaboratory()) {
            throw new ApplicationException('La ubicación indicada no es un laboratorio.');
        }

        $stock->markDamaged();
        $this->serializedStockRepository->save($stock);

        $repair = Repair::assign(
            id:           new RepairIdValueObject($this->idGenerator->generate()),
            serializedStockId: $stockId,
            laboratoryId: $labId,
            assignedById: new UserIdValueObject($command->assignedById),
            notes:        $command->notes,
        );

        $this->repairRepository->save($repair);
    }
}
