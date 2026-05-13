<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ConfirmEntry;

use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Repositories\EntryItemRepositoryInterface;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ConfirmEntryHandler
{
    public function __construct(
        private EntryRepositoryInterface $entryRepository,
        private EntryItemRepositoryInterface $entryItemRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private MovementRepositoryInterface $movementRepository,
        private PurchaseOrderItemRepositoryInterface $purchaseOrderItemRepository,
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(ConfirmEntryCommand $command): void
    {
        $id    = new EntryIdValueObject($command->id);
        $entry = $this->entryRepository->findById($id)
            ?? throw EntryNotFoundException::withId($command->id);

        $entry->confirm();
        $this->entryRepository->save($entry);

        $items = $this->entryItemRepository->findByEntry($id);

        foreach ($items as $item) {
            if ($item->isSerialized()) {
                $stock = SerializedStock::register(
                    id:           new SerializedStockIdValueObject($this->idGenerator->generate()),
                    productId:    $item->productId(),
                    serialNumber: $item->serialNumber(),
                    locationId:   $item->locationId(),
                );
                $this->serializedStockRepository->save($stock);

                $movement = Movement::create(
                    id:                    new MovementIdValueObject($this->idGenerator->generate()),
                    productId:             $item->productId(),
                    type:                  MovementType::ENTRY,
                    sourceLocationId:      null,
                    destinationLocationId: $item->locationId(),
                    serializedStockId:     $stock->id(),
                    quantity:              1,
                    notes:                 'Entrada confirmada: ' . $entry->reference(),
                );
                $movement->complete();
                $this->movementRepository->save($movement);
            } else {
                $existing = $this->nonserializedStockRepository->findByProductAndLocation(
                    $item->productId(),
                    $item->locationId(),
                );

                if ($existing !== null && $existing->isAvailable()) {
                    $existing->addStock($item->quantity());
                    $this->nonserializedStockRepository->save($existing);
                } else {
                    $newStock = NonserializedStock::initialize(
                        id:              new NonserializedStockIdValueObject($this->idGenerator->generate()),
                        productId:       $item->productId(),
                        locationId:      $item->locationId(),
                        initialQuantity: $item->quantity(),
                    );
                    $this->nonserializedStockRepository->save($newStock);
                }

                $movement = Movement::create(
                    id:                    new MovementIdValueObject($this->idGenerator->generate()),
                    productId:             $item->productId(),
                    type:                  MovementType::ENTRY,
                    sourceLocationId:      null,
                    destinationLocationId: $item->locationId(),
                    serializedStockId:     null,
                    quantity:              $item->quantity(),
                    notes:                 'Entrada confirmada: ' . $entry->reference(),
                );
                $movement->complete();
                $this->movementRepository->save($movement);
            }

            if ($item->purchaseOrderItemId() !== null) {
                $poItem = $this->purchaseOrderItemRepository->findById($item->purchaseOrderItemId());
                if ($poItem !== null) {
                    $poItem->receive($item->quantity());
                    $this->purchaseOrderItemRepository->save($poItem);

                    if ($poItem->isFullyReceived()) {
                        $poItems = $this->purchaseOrderItemRepository->findByPurchaseOrder($poItem->purchaseOrderId());
                        $allFulfilled = true;
                        foreach ($poItems as $pi) {
                            if (!$pi->isFullyReceived()) {
                                $allFulfilled = false;
                                break;
                            }
                        }
                        $po = $this->purchaseOrderRepository->findById($poItem->purchaseOrderId());
                        if ($po !== null) {
                            $allFulfilled ? $po->markCompleted() : $po->markPartial();
                            $this->purchaseOrderRepository->save($po);
                        }
                    }
                }
            }
        }
    }
}
