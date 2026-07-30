<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ReturnFromCustody;

use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\StockNotFoundException;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;

final readonly class ReturnFromCustodyHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private MovementRepositoryInterface $movementRepository,
        private IdGeneratorInterface $idGenerator,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ReturnFromCustodyCommand $command): void
    {
        $this->transactionManager->begin();

        try {
            $custodyLocation = $this->locationRepository->findById(
                new LocationIdValueObject($command->custodyLocationId),
            );

            if ($custodyLocation === null) {
                throw LocationNotFoundException::withId($command->custodyLocationId);
            }

            if (!$custodyLocation->isCustody()) {
                throw new InventoryDomainException('La ubicación de origen debe ser de tipo CUSTODY.');
            }

            $returnLocation = $this->locationRepository->findById(
                new LocationIdValueObject($command->returnLocationId),
            );

            if ($returnLocation === null) {
                throw LocationNotFoundException::withId($command->returnLocationId);
            }

            $stockItem = $this->serializedStockRepository->findById(
                new SerializedStockIdValueObject($command->stockItemId),
            );

            if ($stockItem === null) {
                throw new StockNotFoundException($command->stockItemId);
            }

            // Verify the stock is at the custody location
            if ((string) $stockItem->locationId() !== $command->custodyLocationId) {
                throw new \InvalidArgumentException('El ítem de stock no se encuentra en la ubicación de custodia especificada.');
            }

            // Complete the transfer: update stock location
            $stockItem->completeTransfer(new LocationIdValueObject($command->returnLocationId));

            // Create return movement
            $movement = Movement::create(
                id:                     new MovementIdValueObject($this->idGenerator->generate()),
                productId:              $stockItem->productId(),
                type:                   MovementType::TRANSFER,
                sourceLocationId:       new LocationIdValueObject($command->custodyLocationId),
                destinationLocationId:  new LocationIdValueObject($command->returnLocationId),
                serializedStockId:      $stockItem->id(),
                quantity:               1,
            );

            $movement->complete();

            $this->serializedStockRepository->save($stockItem);
            $this->movementRepository->save($movement);

            $this->transactionManager->commit();
        } catch (\Throwable $e) {
            $this->transactionManager->rollback();

            throw new ApplicationException('Error al procesar la solicitud.', 0, $e);
        }
    }
}
