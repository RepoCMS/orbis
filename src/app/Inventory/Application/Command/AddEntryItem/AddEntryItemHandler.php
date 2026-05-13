<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AddEntryItem;

use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Domain\Entities\EntryItem;
use App\Inventory\Domain\Repositories\EntryItemRepositoryInterface;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Inventory\Domain\ValueObjects\EntryItemIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class AddEntryItemHandler
{
    public function __construct(
        private EntryRepositoryInterface $entryRepository,
        private EntryItemRepositoryInterface $entryItemRepository,
        private ProductRepositoryInterface $productRepository,
        private LocationRepositoryInterface $locationRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(AddEntryItemCommand $command): void
    {
        $entryId = new EntryIdValueObject($command->entryId);
        $this->entryRepository->findById($entryId)
            ?? throw EntryNotFoundException::withId($command->entryId);

        $productId = new ProductIdValueObject($command->productId);
        $this->productRepository->findById($productId)
            ?? throw ProductNotFoundException::withId($command->productId);

        $locationId = new LocationIdValueObject($command->locationId);
        $this->locationRepository->findById($locationId)
            ?? throw LocationNotFoundException::withId($command->locationId);

        $itemId = new EntryItemIdValueObject($this->idGenerator->generate());
        $poItemId = $command->purchaseOrderItemId !== null
            ? new PurchaseOrderItemIdValueObject($command->purchaseOrderItemId)
            : null;

        if ($command->serialNumber !== null) {
            $item = EntryItem::createSerialized(
                id:                  $itemId,
                entryId:             $entryId,
                productId:           $productId,
                locationId:          $locationId,
                serialNumber:        new StockSerialNumberValueObject($command->serialNumber),
                purchaseOrderItemId: $poItemId,
            );
        } else {
            $item = EntryItem::createNonserialized(
                id:                  $itemId,
                entryId:             $entryId,
                productId:           $productId,
                locationId:          $locationId,
                quantity:            $command->quantity,
                purchaseOrderItemId: $poItemId,
            );
        }

        $this->entryItemRepository->save($item);
    }
}
