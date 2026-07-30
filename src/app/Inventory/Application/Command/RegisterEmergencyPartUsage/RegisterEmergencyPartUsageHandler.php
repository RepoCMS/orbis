<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RegisterEmergencyPartUsage;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\SerialHistoryReason;
use App\Inventory\Domain\Events\EmergencyPartUsed;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerialHistoryRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\SerialHistoryIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class RegisterEmergencyPartUsageHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private LocationRepositoryInterface $locationRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private SerialHistoryRepositoryInterface $serialHistoryRepository,
        private DomainEventPublisherInterface $eventPublisher,
        private IdGeneratorInterface $idGenerator,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(RegisterEmergencyPartUsageCommand $command): void
    {
        $this->transactionManager->begin();

        try {
            // 1. Find product by code
            $productCode = new ProductCodeAssignedBySupplierValueObject($command->productCode);
            $product = $this->productRepository->findByCode($productCode);

            if ($product === null) {
                throw new \InvalidArgumentException(
                    sprintf('Producto con código "%s" no encontrado.', $command->productCode)
                );
            }

            // 2. Find a TECHNICIAN backpack location
            $backpacks = $this->locationRepository->findByType(LocationType::BACKPACK);

            if (empty($backpacks)) {
                throw new \LogicException('No se encontró ninguna ubicación de tipo BACKPACK para el técnico.');
            }

            // 3. Find the serialized stock by product and serial number
            $serialNumber = new StockSerialNumberValueObject($command->serialNumber);
            $stock = $this->serializedStockRepository->findBySerial(
                productId: $product->id(),
                serialNumber: $serialNumber,
            );

            if ($stock === null) {
                throw new \InvalidArgumentException(
                    sprintf('Stock serializado no encontrado para producto "%s" y serie "%s".', $command->productCode, $command->serialNumber)
                );
            }

            // 4. Move stock to SUSPECT status
            $stock->markSuspect();
            $this->serializedStockRepository->save($stock);

            // 5. Record SerialHistory with SERIAL_ASSIGNMENT reason
            $serialHistory = \App\Inventory\Domain\Entities\SerialHistory::record(
                id:                   new SerialHistoryIdValueObject($this->idGenerator->generate()),
                serializedStockId:    $stock->id(),
                previousSerialNumber: $stock->serialNumber(),
                newSerialNumber:      $stock->serialNumber(),
                reason:               SerialHistoryReason::SERIAL_ASSIGNMENT,
                previousProductId:    $stock->productId(),
                newProductId:         $stock->productId(),
                changedBy:            new UserIdValueObject($command->changedBy),
            );

            $this->serialHistoryRepository->save($serialHistory);

            // 6. Publish EmergencyPartUsed event
            $this->eventPublisher->publish(new EmergencyPartUsed(
                productId:         (string) $product->id(),
                serializedStockId: (string) $stock->id(),
                employeeId:        $command->employeeId,
                notes:             $command->notes,
            ));

            $this->transactionManager->commit();
        } catch (\Throwable $e) {
            $this->transactionManager->rollback();

            throw $e;
        }
    }
}
