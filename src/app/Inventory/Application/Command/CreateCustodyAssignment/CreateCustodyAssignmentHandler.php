<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateCustodyAssignment;

use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\StockNotFoundException;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Application\IdempotentHandlerTrait;
use App\Shared\Application\Service\IdempotencyService;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;

final readonly class CreateCustodyAssignmentHandler
{
    use IdempotentHandlerTrait;

    public function __construct(
        private LocationRepositoryInterface $locationRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private MovementRepositoryInterface $movementRepository,
        private IdGeneratorInterface $idGenerator,
        private EmployeeRepositoryInterface $employeeRepository,
        private TransactionManagerInterface $transactionManager,
        private IdempotencyServiceInterface $idempotencyService,
    ) {
    }

    public function __invoke(CreateCustodyAssignmentCommand $command): void
    {
        $this->ensureUnique(
            IdempotencyService::buildKey(CreateCustodyAssignmentCommand::class, serialize($command)),
            CreateCustodyAssignmentCommand::class,
            '',
        );

        $this->transactionManager->begin();

        try {
            // Cross-BC: validate employee exists and is active
            $employee = $this->employeeRepository->findById(
                EmployeeIdValueObject::fromString($command->employeeId),
            );

            if ($employee === null) {
                throw new ApplicationException(sprintf(
                    'Empleado con ID "%s" no encontrado.',
                    $command->employeeId,
                ));
            }

            if (!$employee->isActive()) {
                throw new ApplicationException(sprintf(
                    'El empleado "%s" no está activo. No se puede asignar custodia.',
                    $employee->fullName(),
                ));
            }

            $sourceLocation = $this->locationRepository->findById(
                new LocationIdValueObject($command->locationId),
            );

            if ($sourceLocation === null) {
                throw LocationNotFoundException::withId($command->locationId);
            }

            $stockItem = $this->serializedStockRepository->findById(
                new SerializedStockIdValueObject($command->stockItemId),
            );

            if ($stockItem === null) {
                throw new StockNotFoundException($command->stockItemId);
            }

            // Find existing CUSTODY location for this employee or create one
            $custodyLocations = $this->locationRepository->findByEmployeeId($command->employeeId);
            $custodyLocation = null;

            foreach ($custodyLocations as $loc) {
                if ($loc->isCustody()) {
                    $custodyLocation = $loc;
                    break;
                }
            }

            if ($custodyLocation === null) {
                // Business rule: un empleado solo puede tener una ubicación asignada.
                // Si ya tiene otra ubicación (warehouse, laboratory, backpack, etc.),
                // no se puede crear una de custodia.
                $otherLocations = array_filter(
                    $custodyLocations,
                    static fn ($l) => !$l->isCustody(),
                );

                if (!empty($otherLocations)) {
                    throw new ApplicationException(
                        'El empleado ya tiene una ubicación asignada y no puede tener custodia adicional.'
                    );
                }

                $custodyLocation = Location::create(
                    id:    new LocationIdValueObject($this->idGenerator->generate()),
                    name:  new LocationNameValueObject(sprintf('Custodia - %s', $command->employeeId)),
                    city:  $sourceLocation->city(),
                    address: $sourceLocation->address(),
                    type:  LocationType::CUSTODY,
                );
            }

            // Reserve the stock item
            $stockItem->reserve();

            // Create movement
            $movement = Movement::create(
                id:                    new MovementIdValueObject($this->idGenerator->generate()),
                productId:             $stockItem->productId(),
                type:                  MovementType::TRANSFER,
                sourceLocationId:      new LocationIdValueObject($command->locationId),
                destinationLocationId: $custodyLocation->id(),
                serializedStockId:     $stockItem->id(),
                quantity:              1,
                notes:                 $command->notes,
            );

            $this->locationRepository->save($custodyLocation);
            $this->serializedStockRepository->save($stockItem);
            $this->movementRepository->save($movement);

            $this->transactionManager->commit();
        } catch (\Throwable $e) {
            $this->transactionManager->rollback();

            throw new ApplicationException('Error al procesar la solicitud.', 0, $e);
        }
    }
}
