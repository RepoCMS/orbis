<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\ConfirmEntry\ConfirmEntryCommand;
use App\Inventory\Application\Command\ConfirmEntry\ConfirmEntryHandler;
use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Entities\EntryItem;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Entities\PurchaseOrderItem;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\EntryStatus;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Enums\PurchaseOrderStatus;
use App\Inventory\Domain\Exceptions\EntryAlreadyConfirmedException;
use App\Inventory\Domain\Repositories\EntryItemRepositoryInterface;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Inventory\Domain\ValueObjects\EntryItemIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class ConfirmEntryHandlerTest extends TestCase
{
    private const ENTRY_ID = '01HZ0000000000000000000100';
    private const REFERENCE = 'PO-2024-001';
    private const CREATED_BY = '01HZ0000000000000000000020';
    private const PRODUCT_ID = '01HZ0000000000000000000003';
    private const LOCATION_ID = '01HZ0000000000000000000001';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000004';
    private const MOVEMENT_ID = '01HZ0000000000000000000010';
    private const ENTRY_ITEM_ID = '01HZ0000000000000000000101';
    private const PO_ITEM_ID = '01HZ0000000000000000000102';
    private const PO_ID = '01HZ0000000000000000000103';

    private function makeDraftEntry(): Entry
    {
        return Entry::create(
            id: new EntryIdValueObject(self::ENTRY_ID),
            reference: self::REFERENCE,
            notes: 'Entrada de prueba',
            createdBy: new \App\Identity\Domain\ValueObjects\UserIdValueObject(self::CREATED_BY),
        );
    }

    private function makeSerializedEntryItem(): EntryItem
    {
        return EntryItem::createSerialized(
            id: new EntryItemIdValueObject(self::ENTRY_ITEM_ID),
            entryId: new EntryIdValueObject(self::ENTRY_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::LOCATION_ID),
            serialNumber: new StockSerialNumberValueObject('SN-001'),
        );
    }

    private function makeNonserializedEntryItem(int $quantity = 5): EntryItem
    {
        return EntryItem::createNonserialized(
            id: new EntryItemIdValueObject(self::ENTRY_ITEM_ID),
            entryId: new EntryIdValueObject(self::ENTRY_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::LOCATION_ID),
            quantity: $quantity,
        );
    }

    // ─── Serialized items ────────────────────────────────────────────────────

    public function testConfirmsWithSerializedItems(): void
    {
        $entry = $this->makeDraftEntry();
        $item = $this->makeSerializedEntryItem();

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);
        $entryRepo->expects($this->once())->method('save')->with($entry);

        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $entryItemRepo->method('findByEntry')->willReturn([$item]);

        $savedSerialized = [];
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (SerializedStock $s) use (&$savedSerialized): void {
                $savedSerialized[] = $s;
            });

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->exactly(2))
            ->method('generate')
            ->willReturn(self::SERIALIZED_STOCK_ID, self::MOVEMENT_ID);

        $handler = new ConfirmEntryHandler(
            entryRepository: $entryRepo,
            entryItemRepository: $entryItemRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            purchaseOrderItemRepository: $poItemRepo,
            purchaseOrderRepository: $poRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new ConfirmEntryCommand(id: self::ENTRY_ID));

        self::assertSame(EntryStatus::CONFIRMED, $entry->status());
        self::assertNotNull($entry->confirmedAt());

        self::assertCount(1, $savedSerialized);
        self::assertSame(self::SERIALIZED_STOCK_ID, (string) $savedSerialized[0]->id());
        self::assertSame(self::LOCATION_ID, (string) $savedSerialized[0]->locationId());
        self::assertTrue($savedSerialized[0]->isAvailable());

        self::assertCount(1, $savedMovements);
        self::assertSame(MovementType::ENTRY, $savedMovements[0]->type());
        self::assertSame(self::LOCATION_ID, (string) $savedMovements[0]->destinationLocationId());
    }

    // ─── Nonserialized items ─────────────────────────────────────────────────

    public function testConfirmsWithNonserializedItemsExistingStock(): void
    {
        $entry = $this->makeDraftEntry();
        $item = $this->makeNonserializedEntryItem(5);
        $existingStock = NonserializedStock::initialize(
            id: new \App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject('01HZ0000000000000000000090'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::LOCATION_ID),
            initialQuantity: 10,
        );

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);
        $entryRepo->expects($this->once())->method('save')->with($entry);

        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $entryItemRepo->method('findByEntry')->willReturn([$item]);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocation')->willReturn($existingStock);
        $nonserializedRepo->expects($this->once())->method('save')->with($existingStock);

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $handler = new ConfirmEntryHandler(
            entryRepository: $entryRepo,
            entryItemRepository: $entryItemRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            purchaseOrderItemRepository: $poItemRepo,
            purchaseOrderRepository: $poRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new ConfirmEntryCommand(id: self::ENTRY_ID));

        self::assertSame(EntryStatus::CONFIRMED, $entry->status());
        self::assertSame(15, $existingStock->quantity(), 'Existing stock should increase from 10 to 15');
        self::assertCount(1, $savedMovements);
    }

    public function testConfirmsWithNonserializedItemsNewLocation(): void
    {
        $entry = $this->makeDraftEntry();
        $item = $this->makeNonserializedEntryItem(5);
        $newStockId = '01HZ0000000000000000000099';

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);
        $entryRepo->expects($this->once())->method('save')->with($entry);

        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $entryItemRepo->method('findByEntry')->willReturn([$item]);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocation')->willReturn(null);

        $savedNewStock = null;
        $nonserializedRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (NonserializedStock $s) use (&$savedNewStock): void {
                $savedNewStock = $s;
            });

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn($newStockId, self::MOVEMENT_ID);

        $handler = new ConfirmEntryHandler(
            entryRepository: $entryRepo,
            entryItemRepository: $entryItemRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            purchaseOrderItemRepository: $poItemRepo,
            purchaseOrderRepository: $poRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new ConfirmEntryCommand(id: self::ENTRY_ID));

        self::assertSame(EntryStatus::CONFIRMED, $entry->status());
        self::assertNotNull($savedNewStock);
        self::assertSame($newStockId, (string) $savedNewStock->id());
        self::assertSame(self::LOCATION_ID, (string) $savedNewStock->locationId());
        self::assertSame(5, $savedNewStock->quantity());
        self::assertTrue($savedNewStock->isAvailable());
        self::assertCount(1, $savedMovements);
    }

    // ─── PO tracking ─────────────────────────────────────────────────────────

    public function testConfirmsWithPurchaseOrderPartialFulfillment(): void
    {
        $entry = $this->makeDraftEntry();
        $item = EntryItem::createNonserialized(
            id: new EntryItemIdValueObject(self::ENTRY_ITEM_ID),
            entryId: new EntryIdValueObject(self::ENTRY_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::LOCATION_ID),
            quantity: 3,
            purchaseOrderItemId: new PurchaseOrderItemIdValueObject(self::PO_ITEM_ID),
        );

        $poItem = PurchaseOrderItem::create(
            id: new PurchaseOrderItemIdValueObject(self::PO_ITEM_ID),
            purchaseOrderId: new PurchaseOrderIdValueObject(self::PO_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            quantityOrdered: 10,
        );

        $poItems = [$poItem];
        $otherPoItem = PurchaseOrderItem::create(
            id: new PurchaseOrderItemIdValueObject('01HZ0000000000000000000199'),
            purchaseOrderId: new PurchaseOrderIdValueObject(self::PO_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            quantityOrdered: 5,
        );

        $po = PurchaseOrder::create(
            id: new PurchaseOrderIdValueObject(self::PO_ID),
            reference: 'PO-2024-001',
            notes: null,
            createdBy: new \App\Identity\Domain\ValueObjects\UserIdValueObject(self::CREATED_BY),
        );

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);
        $entryRepo->expects($this->once())->method('save')->with($entry);

        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $entryItemRepo->method('findByEntry')->willReturn([$item]);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocation')->willReturn(null);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('save');

        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poItemRepo->method('findById')->willReturn($poItem);
        $poItemRepo->expects($this->once())->method('save')->with($poItem);
        $poItemRepo->method('findByPurchaseOrder')->willReturn([$poItem, $otherPoItem]);

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findById')->willReturn($po);
        $poRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $handler = new ConfirmEntryHandler(
            entryRepository: $entryRepo,
            entryItemRepository: $entryItemRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            purchaseOrderItemRepository: $poItemRepo,
            purchaseOrderRepository: $poRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new ConfirmEntryCommand(id: self::ENTRY_ID));

        self::assertSame(3, $poItem->quantityReceived());
        self::assertFalse($poItem->isFullyReceived(), 'Item with 3/10 should not be fully received');
        self::assertSame(PurchaseOrderStatus::OPEN, $po->status(), 'PO should remain open when not all items fulfilled');
    }

    public function testConfirmsWithPurchaseOrderFullFulfillment(): void
    {
        $entry = $this->makeDraftEntry();
        $item = EntryItem::createNonserialized(
            id: new EntryItemIdValueObject(self::ENTRY_ITEM_ID),
            entryId: new EntryIdValueObject(self::ENTRY_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::LOCATION_ID),
            quantity: 10,
            purchaseOrderItemId: new PurchaseOrderItemIdValueObject(self::PO_ITEM_ID),
        );

        $poItem = PurchaseOrderItem::create(
            id: new PurchaseOrderItemIdValueObject(self::PO_ITEM_ID),
            purchaseOrderId: new PurchaseOrderIdValueObject(self::PO_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            quantityOrdered: 10,
        );

        $po = PurchaseOrder::create(
            id: new PurchaseOrderIdValueObject(self::PO_ID),
            reference: 'PO-2024-001',
            notes: null,
            createdBy: new \App\Identity\Domain\ValueObjects\UserIdValueObject(self::CREATED_BY),
        );

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);
        $entryRepo->expects($this->once())->method('save')->with($entry);

        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $entryItemRepo->method('findByEntry')->willReturn([$item]);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocation')->willReturn(null);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('save');

        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poItemRepo->method('findById')->willReturn($poItem);
        $poItemRepo->expects($this->once())->method('save')->with($poItem);
        $poItemRepo->method('findByPurchaseOrder')->willReturn([$poItem]);

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findById')->willReturn($po);
        $poRepo->expects($this->once())->method('save')->with($po);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $handler = new ConfirmEntryHandler(
            entryRepository: $entryRepo,
            entryItemRepository: $entryItemRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            purchaseOrderItemRepository: $poItemRepo,
            purchaseOrderRepository: $poRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new ConfirmEntryCommand(id: self::ENTRY_ID));

        self::assertTrue($poItem->isFullyReceived());
        self::assertSame(PurchaseOrderStatus::COMPLETED, $po->status(), 'PO should be completed when all items fulfilled');
    }

    // ─── Error cases ─────────────────────────────────────────────────────────

    public function testThrowsWhenEntryNotFound(): void
    {
        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn(null);

        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new ConfirmEntryHandler(
            entryRepository: $entryRepo,
            entryItemRepository: $entryItemRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            purchaseOrderItemRepository: $poItemRepo,
            purchaseOrderRepository: $poRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage('Entrada con ID "' . self::ENTRY_ID . '" no encontrada.');

        ($handler)(new ConfirmEntryCommand(id: self::ENTRY_ID));
    }

    public function testThrowsWhenEntryAlreadyConfirmed(): void
    {
        $entry = $this->makeDraftEntry();
        $entry->confirm();

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);

        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new ConfirmEntryHandler(
            entryRepository: $entryRepo,
            entryItemRepository: $entryItemRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            purchaseOrderItemRepository: $poItemRepo,
            purchaseOrderRepository: $poRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(EntryAlreadyConfirmedException::class);

        ($handler)(new ConfirmEntryCommand(id: self::ENTRY_ID));
    }

    public function testHandlesMixedSerializedAndNonserializedItems(): void
    {
        $entry = $this->makeDraftEntry();
        $serializedItem = $this->makeSerializedEntryItem();
        $nonserializedItem = $this->makeNonserializedEntryItem(3);

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);
        $entryRepo->expects($this->once())->method('save')->with($entry);

        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $entryItemRepo->method('findByEntry')->willReturn([$serializedItem, $nonserializedItem]);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->expects($this->once())->method('save');

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocation')->willReturn(null);
        $nonserializedRepo->expects($this->once())->method('save');

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->exactly(2))->method('save');

        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')
            ->willReturn(
                self::SERIALIZED_STOCK_ID,
                self::MOVEMENT_ID,
                '01HZ0000000000000000000099',
                '01HZ0000000000000000000011',
            );

        $handler = new ConfirmEntryHandler(
            entryRepository: $entryRepo,
            entryItemRepository: $entryItemRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            purchaseOrderItemRepository: $poItemRepo,
            purchaseOrderRepository: $poRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new ConfirmEntryCommand(id: self::ENTRY_ID));

        self::assertSame(EntryStatus::CONFIRMED, $entry->status());
    }
}
