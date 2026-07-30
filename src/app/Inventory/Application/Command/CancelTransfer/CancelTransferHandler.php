<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelTransfer;

use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;

final readonly class CancelTransferHandler
{
    public function __construct(
        private MovementRepositoryInterface $movementRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(CancelTransferCommand $command): void
    {
        $movement = $this->movementRepository->findById(new MovementIdValueObject($command->movementId));

        if ($movement === null) {
            throw new InventoryDomainException('Movimiento no encontrado.');
        }

        $movement->cancelBy($command->cancelledBy);

        $this->transactionManager->begin();

        try {
            if ($movement->serializedStockId() !== null) {
                $stock = $this->serializedStockRepository->findById($movement->serializedStockId());

                if ($stock === null) {
                    throw new InventoryDomainException('Stock serializado no encontrado.');
                }

                $stock->release();
                $this->serializedStockRepository->save($stock);
            } else {
                $sourceLocationId = $movement->sourceLocationId();
                $productId        = $movement->productId();

                if ($sourceLocationId !== null) {
                    $reservedStock = $this->nonserializedStockRepository->findByProductAndLocationAndStatus(
                        $productId,
                        $sourceLocationId,
                        NonserializedStockStatus::RESERVED,
                    );

                    if ($reservedStock !== null) {
                        $availableStock = $this->nonserializedStockRepository->findByProductAndLocationAndStatus(
                            $productId,
                            $sourceLocationId,
                            NonserializedStockStatus::AVAILABLE,
                        );

                        if ($availableStock !== null) {
                            $availableStock->addStock($reservedStock->quantity());
                            $this->nonserializedStockRepository->remove($reservedStock);
                        } else {
                            $reservedStock->release();
                            $this->nonserializedStockRepository->save($reservedStock);
                        }
                    } else {
                        throw new InventoryDomainException(
                            'No se puede cancelar: el stock reservado no se encontró.'
                        );
                    }
                }
            }

            $this->movementRepository->save($movement);

            $this->transactionManager->commit();
        } catch (\Throwable $e) {
            $this->transactionManager->rollback();

            if ($e instanceof DomainException) {
                throw $e;
            }

            throw new ApplicationException('Error al cancelar la transferencia.', 0, $e);
        }
    }
}
