<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ConfirmTransfer;

use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;

final readonly class ConfirmTransferHandler
{
    public function __construct(
        private MovementRepositoryInterface $movementRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private IdGeneratorInterface $idGenerator,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ConfirmTransferCommand $command): void
    {
        $movement = $this->movementRepository->findById(new MovementIdValueObject($command->movementId));

        if ($movement === null) {
            throw new InventoryDomainException('Movimiento no encontrado.');
        }

        $destinationLocationId = $movement->destinationLocationId();

        if ($destinationLocationId === null) {
            throw new InventoryDomainException('El movimiento no tiene ubicación de destino.');
        }

        $movement->confirmBy($command->confirmedBy);

        $this->transactionManager->begin();

        try {
            if ($movement->serializedStockId() !== null) {
                $stock = $this->serializedStockRepository->findById($movement->serializedStockId());

                if ($stock === null) {
                    throw new InventoryDomainException('Stock serializado no encontrado.');
                }

                $stock->completeTransfer($destinationLocationId);
                $this->serializedStockRepository->save($stock);
            } else {
                $productId = $movement->productId();
                $quantity  = $movement->quantity();
                $sourceLocationId = $movement->sourceLocationId();

                if ($sourceLocationId !== null) {
                    $reservedStock = $this->nonserializedStockRepository->findByProductAndLocationAndStatus(
                        $productId,
                        $sourceLocationId,
                        NonserializedStockStatus::RESERVED,
                    );

                    if ($reservedStock !== null) {
                        $this->nonserializedStockRepository->remove($reservedStock);
                    }
                }

                $destStock = $this->nonserializedStockRepository->findByProductAndLocation(
                    $productId,
                    $destinationLocationId,
                );

                if ($destStock !== null && $destStock->isAvailable()) {
                    $destStock->addStock($quantity);
                    $this->nonserializedStockRepository->save($destStock);
                } else {
                    $newStock = NonserializedStock::initialize(
                        id:              new NonserializedStockIdValueObject($this->idGenerator->generate()),
                        productId:       $productId,
                        locationId:      $destinationLocationId,
                        initialQuantity: $quantity,
                    );
                    $this->nonserializedStockRepository->save($newStock);
                }
            }

            $this->movementRepository->save($movement);

            $this->transactionManager->commit();
        } catch (\Throwable $e) {
            $this->transactionManager->rollback();

            if ($e instanceof DomainException) {
                throw $e;
            }

            throw new ApplicationException('Error al confirmar la transferencia.', 0, $e);
        }
    }
}
