<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateTransfer;

use App\Inventory\Application\Service\TransferAuthorizationService;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Exceptions\InsufficientStockException;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;

final readonly class CreateTransferHandler
{
    public function __construct(
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private LocationRepositoryInterface $locationRepository,
        private MovementRepositoryInterface $movementRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private IdGeneratorInterface $idGenerator,
        private TransactionManagerInterface $transactionManager,
        private TransferAuthorizationService $transferAuth,
    ) {
    }

    public function __invoke(CreateTransferCommand $command): void
    {
        $destinationLocationId = new LocationIdValueObject($command->destinationLocationId);

        $destLocation = $this->locationRepository->findById($destinationLocationId);
        if ($destLocation === null) {
            throw new InventoryDomainException('La ubicación de destino no existe.');
        }

        if ($command->items === []) {
            throw new InventoryDomainException('Debe seleccionar al menos un item para transferir.');
        }

        $assignedToUserId = $this->resolveAssignedUser($destLocation);

        /** @var array<string, bool> $sourceLocationIds */
        $sourceLocationIds = [];

        foreach ($command->items as $item) {
            $stockId = $item['stockId'];
            $type    = $item['type'];

            $source = match ($type) {
                'serialized' => $this->serializedStockRepository->findById(new SerializedStockIdValueObject($stockId)),
                default      => $this->nonserializedStockRepository->findById(new NonserializedStockIdValueObject($stockId)),
            };

            if ($source === null) {
                throw new InventoryDomainException(sprintf('Item %s no encontrado.', $stockId));
            }

            $sourceLocationIds[(string) $source->locationId()] = true;
        }

        $this->transferAuth->authorize($command->createdBy, array_keys($sourceLocationIds), $command->destinationLocationId);

        $this->transactionManager->begin();

        try {
            foreach ($command->items as $item) {
                $productId = $item['productId'];
                $stockId   = $item['stockId'];
                $type      = $item['type'];
                $quantity  = (int) ($item['quantity'] ?? 1);

                $movementId = new MovementIdValueObject($this->idGenerator->generate());

                if ($type === 'serialized') {
                    $stock = $this->serializedStockRepository->findById(new SerializedStockIdValueObject($stockId));

                    if ($stock === null) {
                        throw new InventoryDomainException(sprintf('Stock serializado %s no encontrado.', $stockId));
                    }

                    $stock->reserve();
                    $this->serializedStockRepository->save($stock);

                    $movement = Movement::create(
                        id:                    $movementId,
                        productId:             new ProductIdValueObject($productId),
                        type:                  MovementType::TRANSFER,
                        sourceLocationId:      $stock->locationId(),
                        destinationLocationId: $destinationLocationId,
                        serializedStockId:     new SerializedStockIdValueObject($stockId),
                        quantity:              1,
                        notes:                 $command->notes,
                        createdByUserId:       $command->createdBy,
                        assignedToUserId:     $assignedToUserId,
                    );
                } else {
                    $sourceStock = $this->nonserializedStockRepository->findById(new NonserializedStockIdValueObject($stockId));

                    if ($sourceStock === null || !$sourceStock->isAvailable()) {
                        throw new InventoryDomainException('Stock no disponible en la ubicación de origen.');
                    }

                    if ($sourceStock->quantity() < $quantity) {
                        throw new InsufficientStockException(
                            sprintf('Stock insuficiente. Disponible: %d, Solicitado: %d.', $sourceStock->quantity(), $quantity),
                        );
                    }

                    $this->nonserializedStockRepository->hardDeleteByProductAndLocationAndStatus(
                        $sourceStock->productId(),
                        $sourceStock->locationId(),
                        NonserializedStockStatus::RESERVED,
                    );

                    $existingReserved = $this->nonserializedStockRepository->findByProductAndLocationAndStatus(
                        $sourceStock->productId(),
                        $sourceStock->locationId(),
                        NonserializedStockStatus::RESERVED,
                    );

                    if ($existingReserved !== null) {
                        $existingReserved->increaseReserved($quantity);
                        $this->nonserializedStockRepository->save($existingReserved);

                        $sourceStock->reduce($quantity);
                        $this->nonserializedStockRepository->save($sourceStock);
                    } else {
                        $remaining = $sourceStock->quantity() - $quantity;

                        if ($remaining > 0) {
                            $sourceStock->reduce($remaining);
                            $sourceStock->reserve();
                            $this->nonserializedStockRepository->save($sourceStock);

                            $availableStock = NonserializedStock::initialize(
                                id: new NonserializedStockIdValueObject($this->idGenerator->generate()),
                                productId: $sourceStock->productId(),
                                locationId: $sourceStock->locationId(),
                                initialQuantity: $remaining,
                            );
                            $this->nonserializedStockRepository->save($availableStock);
                        } else {
                            $sourceStock->reserve();
                            $this->nonserializedStockRepository->save($sourceStock);
                        }
                    }

                    $movement = Movement::create(
                        id:                    $movementId,
                        productId:             new ProductIdValueObject($productId),
                        type:                  MovementType::TRANSFER,
                        sourceLocationId:      $sourceStock->locationId(),
                        destinationLocationId: $destinationLocationId,
                        serializedStockId:     null,
                        quantity:              $quantity,
                        notes:                 $command->notes,
                        createdByUserId:       $command->createdBy,
                        assignedToUserId:     $assignedToUserId,
                    );
                }

                $movement->startTransit();
                $this->movementRepository->save($movement);
            }

            $this->transactionManager->commit();
        } catch (\Throwable $e) {
            $this->transactionManager->rollback();

            if ($e instanceof DomainException) {
                throw $e;
            }

            throw new ApplicationException('Error al procesar la transferencia.', 0, $e);
        }
    }

    private function resolveAssignedUser(\App\Inventory\Domain\Entities\Location $destLocation): ?string
    {
        $employeeIdStr = $destLocation->employeeId();

        if ($employeeIdStr === null || $employeeIdStr === '') {
            return null;
        }

        $employee = $this->employeeRepository->findById(new EmployeeIdValueObject($employeeIdStr));

        if ($employee === null) {
            return null;
        }

        return $employee->userId();
    }
}
