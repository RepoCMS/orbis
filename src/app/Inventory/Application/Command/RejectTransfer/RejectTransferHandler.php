<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RejectTransfer;

use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;

final readonly class RejectTransferHandler
{
    public function __construct(
        private MovementRepositoryInterface $movementRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private IdGeneratorInterface $idGenerator,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(RejectTransferCommand $command): void
    {
        $movement = $this->movementRepository->findById(new MovementIdValueObject($command->movementId));

        if ($movement === null) {
            throw new InventoryDomainException('Movimiento no encontrado.');
        }

        if (!$movement->isInTransit()) {
            throw new InventoryDomainException('Solo se puede rechazar una transferencia en tránsito.');
        }

        $rejectQty = $command->quantity;
        $totalQty  = $movement->quantity();

        if ($rejectQty <= 0 || $rejectQty > $totalQty) {
            throw new InventoryDomainException(
                sprintf('La cantidad a rechazar debe ser entre 1 y %d.', $totalQty),
            );
        }

        $isFullReject = ($rejectQty === $totalQty);

        $this->transactionManager->begin();

        try {
            if ($movement->serializedStockId() !== null) {
                if (!$isFullReject) {
                    throw new InventoryDomainException(
                        'No se puede rechazar parcialmente un item serializado.'
                    );
                }

                $stock = $this->serializedStockRepository->findById($movement->serializedStockId());

                if ($stock === null) {
                    throw new InventoryDomainException('Stock serializado no encontrado.');
                }

                $stock->release();
                $this->serializedStockRepository->save($stock);
            } else {
                $sourceLocationId = $movement->sourceLocationId();
                $productId        = $movement->productId();

                if ($sourceLocationId === null) {
                    throw new InventoryDomainException('El movimiento no tiene ubicación de origen.');
                }

                $reservedStock = $this->nonserializedStockRepository->findByProductAndLocationAndStatus(
                    $productId,
                    $sourceLocationId,
                    NonserializedStockStatus::RESERVED,
                );

                if ($reservedStock === null) {
                    throw new InventoryDomainException(
                        'No se puede rechazar: el stock reservado no se encontró.'
                    );
                }

                if ($isFullReject) {
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
                    $reservedStock->reduce($rejectQty);
                    $this->nonserializedStockRepository->save($reservedStock);

                    $sourceStock = $this->nonserializedStockRepository->findByProductAndLocationAndStatus(
                        $productId,
                        $sourceLocationId,
                        NonserializedStockStatus::AVAILABLE,
                    );

                    if ($sourceStock !== null) {
                        $sourceStock->addStock($rejectQty);
                        $this->nonserializedStockRepository->save($sourceStock);
                    } else {
                        $newStock = NonserializedStock::initialize(
                            id:              new NonserializedStockIdValueObject($this->idGenerator->generate()),
                            productId:       $productId,
                            locationId:      $sourceLocationId,
                            initialQuantity: $rejectQty,
                        );
                        $this->nonserializedStockRepository->save($newStock);
                    }
                }
            }

            if ($isFullReject) {
                $movement->rejectBy($command->rejectedBy, $command->reason);
            } else {
                $partialNote = sprintf(
                    'Rechazo parcial: %d de %d devueltos — %s',
                    $rejectQty,
                    $totalQty,
                    $command->reason,
                );
                $existingNotes = $movement->notes();
                $newNotes      = $existingNotes !== null
                    ? $existingNotes . "\n" . $partialNote
                    : $partialNote;

                $movement->recordPartialRejection($command->rejectedBy, $newNotes, $rejectQty);
            }

            $this->movementRepository->save($movement);

            $this->transactionManager->commit();
        } catch (\Throwable $e) {
            $this->transactionManager->rollback();

            if ($e instanceof DomainException) {
                throw $e;
            }

            throw new ApplicationException('Error al rechazar la transferencia.', 0, $e);
        }
    }
}
