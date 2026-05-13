<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\FulfillRequest;

use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Exceptions\InsufficientStockException;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class FulfillRequestHandler
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private MovementRepositoryInterface $movementRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(FulfillRequestCommand $command): void
    {
        $request = $this->requestRepository->findById(new RequestIdValueObject($command->requestId))
            ?? throw RequestNotFoundException::withId($command->requestId);

        $movementId = new MovementIdValueObject($this->idGenerator->generate());

        if ($request->serializedStockId() !== null) {
            // ── Serialized stock ──────────────────────────────────────────────
            $stock = $this->serializedStockRepository->findById($request->serializedStockId());

            if ($stock === null) {
                throw new \LogicException('El stock serializado solicitado no existe.');
            }

            if (!$stock->isAvailable()) {
                throw new \LogicException(
                    sprintf('El stock serializado no está disponible (estado: %s).', $stock->status()->value),
                );
            }

            $stock->completeTransfer($request->destinationLocationId());
            $this->serializedStockRepository->save($stock);

            $movement = Movement::create(
                id:                    $movementId,
                productId:             $request->productId(),
                type:                  MovementType::TRANSFER,
                sourceLocationId:      $request->sourceLocationId(),
                destinationLocationId: $request->destinationLocationId(),
                serializedStockId:     $request->serializedStockId(),
                quantity:              1,
                notes:                 'Solicitud ejecutada: ' . $request->reference(),
            );
        } else {
            // ── Non-serialized stock ──────────────────────────────────────────
            $sourceStock = $this->nonserializedStockRepository->findByProductAndLocation(
                $request->productId(),
                $request->sourceLocationId(),
            );

            if ($sourceStock === null || !$sourceStock->isAvailable()) {
                throw new InsufficientStockException(
                    'No hay stock disponible en la ubicación de origen.',
                );
            }

            $sourceStock->reduce($request->quantity());
            $this->nonserializedStockRepository->save($sourceStock);

            $destStock = $this->nonserializedStockRepository->findByProductAndLocation(
                $request->productId(),
                $request->destinationLocationId(),
            );

            if ($destStock !== null && $destStock->isAvailable()) {
                $destStock->addStock($request->quantity());
                $this->nonserializedStockRepository->save($destStock);
            } else {
                $newStock = NonserializedStock::initialize(
                    id:              new NonserializedStockIdValueObject($this->idGenerator->generate()),
                    productId:       $request->productId(),
                    locationId:      $request->destinationLocationId(),
                    initialQuantity: $request->quantity(),
                );
                $this->nonserializedStockRepository->save($newStock);
            }

            $movement = Movement::create(
                id:                    $movementId,
                productId:             $request->productId(),
                type:                  MovementType::TRANSFER,
                sourceLocationId:      $request->sourceLocationId(),
                destinationLocationId: $request->destinationLocationId(),
                serializedStockId:     null,
                quantity:              $request->quantity(),
                notes:                 'Solicitud ejecutada: ' . $request->reference(),
            );
        }

        $movement->complete();
        $this->movementRepository->save($movement);

        $request->fulfill($movementId);
        $this->requestRepository->save($request);
    }
}
