<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\FulfillRequestItem;

use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\Events\RequestFulfilled;
use App\Inventory\Domain\Events\RequestItemFulfilled;
use App\Inventory\Domain\Exceptions\InsufficientStockException;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;

final readonly class FulfillRequestItemHandler
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private MovementRepositoryInterface $movementRepository,
        private DomainEventPublisherInterface $eventPublisher,
        private IdGeneratorInterface $idGenerator,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(FulfillRequestItemCommand $command): void
    {
        $this->transactionManager->begin();

        try {
            $request = $this->requestRepository->findById(new RequestIdValueObject($command->requestId))
                ?? throw RequestNotFoundException::withId($command->requestId);

            $itemId = new RequestItemIdValueObject($command->itemId);

            $item = null;
            foreach ($request->items() as $i) {
                if ((string) $i->id() === (string) $itemId) {
                    $item = $i;
                    break;
                }
            }

            if ($item === null) {
                throw new \InvalidArgumentException('Item no encontrado en la solicitud.');
            }

            $movementId = new MovementIdValueObject($this->idGenerator->generate());

            if ($item->serializedStockId() !== null) {
                $stock = $this->serializedStockRepository->findById($item->serializedStockId());

                if ($stock === null) {
                    throw new DomainException('El stock serializado solicitado no existe.');
                }

                if (!$stock->isAvailable()) {
                    throw new DomainException(
                        sprintf('El stock serializado no está disponible (estado: %s).', $stock->status()->value),
                    );
                }

                $stock->completeTransfer($request->destinationLocationId());
                $this->serializedStockRepository->save($stock);

                $movement = Movement::create(
                    id:                    $movementId,
                    productId:             $item->productId(),
                    type:                  MovementType::TRANSFER,
                    sourceLocationId:      $request->sourceLocationId(),
                    destinationLocationId: $request->destinationLocationId(),
                    serializedStockId:     $item->serializedStockId(),
                    quantity:              1,
                    notes:                 'Solicitud ejecutada: ' . $request->reference(),
                );
            } else {
                $sourceStock = $this->nonserializedStockRepository->findByProductAndLocation(
                    $item->productId(),
                    $request->sourceLocationId(),
                );

                if ($sourceStock === null || !$sourceStock->isAvailable()) {
                    throw new InsufficientStockException(
                        'No hay stock disponible en la ubicación de origen.',
                    );
                }

                $sourceStock->reduce($item->quantity());
                $this->nonserializedStockRepository->save($sourceStock);

                $destStock = $this->nonserializedStockRepository->findByProductAndLocation(
                    $item->productId(),
                    $request->destinationLocationId(),
                );

                if ($destStock !== null && $destStock->isAvailable()) {
                    $destStock->addStock($item->quantity());
                    $this->nonserializedStockRepository->save($destStock);
                } else {
                    $newStock = NonserializedStock::initialize(
                        id:              new NonserializedStockIdValueObject($this->idGenerator->generate()),
                        productId:       $item->productId(),
                        locationId:      $request->destinationLocationId(),
                        initialQuantity: $item->quantity(),
                    );
                    $this->nonserializedStockRepository->save($newStock);
                }

                $movement = Movement::create(
                    id:                    $movementId,
                    productId:             $item->productId(),
                    type:                  MovementType::TRANSFER,
                    sourceLocationId:      $request->sourceLocationId(),
                    destinationLocationId: $request->destinationLocationId(),
                    serializedStockId:     null,
                    quantity:              $item->quantity(),
                    notes:                 'Solicitud ejecutada: ' . $request->reference(),
                );
            }

            $movement->complete();
            $this->movementRepository->save($movement);

            $request->fulfillItem($itemId, $movementId);
            $this->requestRepository->save($request);

            $this->eventPublisher->publish(new RequestItemFulfilled(
                requestId:         (string) $request->id(),
                requestReference:  $request->reference(),
                itemId:            (string) $item->id(),
                productId:         (string) $item->productId(),
                quantity:          $item->quantity(),
                movementId:        (string) $movementId,
            ));

            if ($request->status() === RequestStatus::FULFILLED) {
                $this->eventPublisher->publish(new RequestFulfilled(
                    requestId:          (string) $request->id(),
                    reference:          $request->reference(),
                    requestedByUserId:  (string) $request->requestedBy(),
                ));
            }

            $this->transactionManager->commit();
        } catch (\Throwable $e) {
            $this->transactionManager->rollback();

            throw new ApplicationException('Error al procesar el item de la solicitud.', 0, $e);
        }
    }
}
