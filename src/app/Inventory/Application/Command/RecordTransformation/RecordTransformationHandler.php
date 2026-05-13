<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RecordTransformation;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Entities\Transformation;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\TransformationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Inventory\Domain\ValueObjects\TransformationIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class RecordTransformationHandler
{
    public function __construct(
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private TransformationRepositoryInterface $transformationRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(RecordTransformationCommand $command): void
    {
        $stock = $this->serializedStockRepository->findById(
            new SerializedStockIdValueObject($command->serializedStockId),
        );

        if ($stock === null) {
            throw new \LogicException('Stock serializado no encontrado.');
        }

        if (!$stock->isAvailable()) {
            throw new \LogicException(
                sprintf('El stock no está disponible para transformación (estado: %s).', $stock->status()->value),
            );
        }

        $newProductId     = $command->newProductId !== null
            ? new ProductIdValueObject($command->newProductId)
            : null;

        $newSerialNumber  = $command->newSerialNumber !== null
            ? new StockSerialNumberValueObject($command->newSerialNumber)
            : null;

        if ($newProductId === null && $newSerialNumber === null) {
            throw new \InvalidArgumentException(
                'Se debe indicar al menos un cambio: nuevo producto o nuevo número de serie.',
            );
        }

        $originalProductId    = $stock->productId();
        $originalSerialNumber = $stock->serialNumber();

        // Apply transformation to the stock item
        $stock->transform($newProductId, $newSerialNumber);
        $this->serializedStockRepository->save($stock);

        // Record transformation audit entry
        $transformation = Transformation::record(
            id:                   new TransformationIdValueObject($this->idGenerator->generate()),
            serializedStockId:    $stock->id(),
            laboratoryId:         new LocationIdValueObject($command->laboratoryId),
            performedById:        new UserIdValueObject($command->performedById),
            originalProductId:    $originalProductId,
            newProductId:         $newProductId,
            originalSerialNumber: $originalSerialNumber,
            newSerialNumber:      $newSerialNumber,
            notes:                $command->notes,
        );

        $this->transformationRepository->save($transformation);
    }
}
