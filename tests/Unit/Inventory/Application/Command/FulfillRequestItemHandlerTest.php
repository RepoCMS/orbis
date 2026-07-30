<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\FulfillRequestItem\FulfillRequestItemCommand;
use App\Inventory\Application\Command\FulfillRequestItem\FulfillRequestItemHandler;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Entities\RequestItem;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Events\RequestFulfilled;
use App\Inventory\Domain\Events\RequestItemFulfilled;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use PHPUnit\Framework\TestCase;

final class FulfillRequestItemHandlerTest extends TestCase
{
    private const REQUEST_ID                   = '01HZ0000000000000000000001';
    private const ITEM_ID                      = '01HZ0000000000000000000002';
    private const PRODUCT_ID                   = '01HZ0000000000000000000003';
    private const SERIALIZED_STOCK_ID          = '01HZ0000000000000000000004';
    private const SOURCE_LOC                   = '01HZ0000000000000000000005';
    private const DEST_LOC                     = '01HZ0000000000000000000006';
    private const MOVEMENT_ID                  = '01HZ0000000000000000000007';
    private const REQUESTED_BY                 = '01HZ0000000000000000000008';
    private const NON_SERIALIZED_SOURCE_STOCK_ID = '01HZ0000000000000000000009';
    private const NON_SERIALIZED_DEST_STOCK_ID   = '01HZ0000000000000000000010';
    private const STOCK_SERIAL_NUMBER          = 'SN-2026-000001';

    private function createSerializedItem(): RequestItem
    {
        return RequestItem::create(
            id: new RequestItemIdValueObject(self::ITEM_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serializedStockId: new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
            quantity: 1,
        );
    }

    private function createNonSerializedItem(): RequestItem
    {
        return RequestItem::create(
            id: new RequestItemIdValueObject(self::ITEM_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serializedStockId: null,
            quantity: 3,
        );
    }

    private function makeRequest(): Request
    {
        return Request::submit(
            id:                   new RequestIdValueObject(self::REQUEST_ID),
            reference:            'SOL-2026-000001',
            requestedBy:          new UserIdValueObject(self::REQUESTED_BY),
            sourceLocationId:     new LocationIdValueObject(self::SOURCE_LOC),
            destinationLocationId: new LocationIdValueObject(self::DEST_LOC),
            notes:                null,
        );
    }

    private function makeSerializedStock(): SerializedStock
    {
        return SerializedStock::register(
            id:           new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
            productId:    new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject(self::STOCK_SERIAL_NUMBER),
            locationId:   new LocationIdValueObject(self::SOURCE_LOC),
        );
    }

    private function makeAvailableSourceStock(): NonserializedStock
    {
        return NonserializedStock::initialize(
            id:              new NonserializedStockIdValueObject(self::NON_SERIALIZED_SOURCE_STOCK_ID),
            productId:       new ProductIdValueObject(self::PRODUCT_ID),
            locationId:      new LocationIdValueObject(self::SOURCE_LOC),
            initialQuantity: 10,
        );
    }

    private function makeAvailableDestStock(): NonserializedStock
    {
        return NonserializedStock::initialize(
            id:              new NonserializedStockIdValueObject(self::NON_SERIALIZED_DEST_STOCK_ID),
            productId:       new ProductIdValueObject(self::PRODUCT_ID),
            locationId:      new LocationIdValueObject(self::DEST_LOC),
            initialQuantity: 0,
        );
    }

    private function setUpTransactionMocks(
        TransactionManagerInterface $transactionManager,
    ): void {
        $transactionManager->expects($this->once())->method('begin');
        $transactionManager->expects($this->once())->method('commit');
        $transactionManager->expects($this->never())->method('rollback');
    }

    public function testFulfillsSerializedItem(): void
    {
        $item = $this->createSerializedItem();
        $request = $this->makeRequest();
        $request->addItem($item);
        $serializedStock = $this->makeSerializedStock();

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);

        $serializedStockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedStockRepo->method('findById')->willReturn($serializedStock);
        $serializedStockRepo->expects($this->once())->method('save')->with($serializedStock);

        $nonserializedStockRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedStockRepo->expects($this->never())->method('findByProductAndLocation');
        $nonserializedStockRepo->expects($this->never())->method('save');

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Movement $movement) {
                return $movement->isCompleted()
                    && $movement->type() === MovementType::TRANSFER
                    && $movement->quantity() === 1;
            }));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publishedEvents = [];
        $publisher->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (...$args) use (&$publishedEvents) {
                foreach ($args as $event) {
                    $publishedEvents[] = $event;
                }
            });

        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $this->setUpTransactionMocks($transactionManager);

        $handler = new FulfillRequestItemHandler(
            requestRepository:          $requestRepo,
            serializedStockRepository:  $serializedStockRepo,
            nonserializedStockRepository: $nonserializedStockRepo,
            movementRepository:         $movementRepo,
            eventPublisher:             $publisher,
            idGenerator:                $idGenerator,
            transactionManager:         $transactionManager,
        );

        ($handler)(new FulfillRequestItemCommand(
            requestId: self::REQUEST_ID,
            itemId:    self::ITEM_ID,
        ));

        self::assertTrue($serializedStock->isAvailable());
        self::assertSame(self::DEST_LOC, (string) $serializedStock->locationId());
        self::assertCount(2, $publishedEvents);
        self::assertInstanceOf(RequestItemFulfilled::class, $publishedEvents[0]);
        self::assertInstanceOf(RequestFulfilled::class, $publishedEvents[1]);
        self::assertSame(self::REQUEST_ID, $publishedEvents[0]->requestId);
        self::assertSame(self::ITEM_ID, $publishedEvents[0]->itemId);
    }

    public function testFulfillsNonSerializedItemWithExistingDestStock(): void
    {
        $item = $this->createNonSerializedItem();
        $request = $this->makeRequest();
        $request->addItem($item);
        $sourceStock = $this->makeAvailableSourceStock();
        $destStock = $this->makeAvailableDestStock();

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);

        $serializedStockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedStockRepo->expects($this->never())->method('findById');
        $serializedStockRepo->expects($this->never())->method('save');

        $nonserializedStockRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedStockRepo->method('findByProductAndLocation')
            ->willReturnCallback(function (ProductIdValueObject $productId, LocationIdValueObject $locationId) use ($sourceStock, $destStock) {
                if ((string) $locationId === self::SOURCE_LOC) {
                    return $sourceStock;
                }
                if ((string) $locationId === self::DEST_LOC) {
                    return $destStock;
                }
                return null;
            });
        $nonserializedStockRepo->expects($this->exactly(2))->method('save');

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Movement $movement) {
                return $movement->isCompleted()
                    && $movement->type() === MovementType::TRANSFER
                    && $movement->quantity() === 3;
            }));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publishedEvents = [];
        $publisher->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (...$args) use (&$publishedEvents) {
                foreach ($args as $event) {
                    $publishedEvents[] = $event;
                }
            });

        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $this->setUpTransactionMocks($transactionManager);

        $handler = new FulfillRequestItemHandler(
            requestRepository:          $requestRepo,
            serializedStockRepository:  $serializedStockRepo,
            nonserializedStockRepository: $nonserializedStockRepo,
            movementRepository:         $movementRepo,
            eventPublisher:             $publisher,
            idGenerator:                $idGenerator,
            transactionManager:         $transactionManager,
        );

        ($handler)(new FulfillRequestItemCommand(
            requestId: self::REQUEST_ID,
            itemId:    self::ITEM_ID,
        ));

        self::assertSame(7, $sourceStock->quantity());
        self::assertSame(3, $destStock->quantity());
        self::assertCount(2, $publishedEvents);
        self::assertInstanceOf(RequestItemFulfilled::class, $publishedEvents[0]);
        self::assertSame(3, $publishedEvents[0]->quantity);
    }

    public function testFulfillsNonSerializedItemCreatesNewDestStock(): void
    {
        $item = $this->createNonSerializedItem();
        $request = $this->makeRequest();
        $request->addItem($item);
        $sourceStock = $this->makeAvailableSourceStock();

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);

        $serializedStockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedStockRepo->expects($this->never())->method('findById');
        $serializedStockRepo->expects($this->never())->method('save');

        $nonserializedStockRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedStockRepo->method('findByProductAndLocation')
            ->willReturnCallback(function (ProductIdValueObject $productId, LocationIdValueObject $locationId) use ($sourceStock) {
                if ((string) $locationId === self::SOURCE_LOC) {
                    return $sourceStock;
                }
                return null;
            });
        $nonserializedStockRepo->expects($this->exactly(2))
            ->method('save');

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Movement $movement) {
                return $movement->isCompleted()
                    && $movement->type() === MovementType::TRANSFER
                    && $movement->quantity() === 3
                    && $movement->serializedStockId() === null;
            }));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publishedEvents = [];
        $publisher->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (...$args) use (&$publishedEvents) {
                foreach ($args as $event) {
                    $publishedEvents[] = $event;
                }
            });

        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $this->setUpTransactionMocks($transactionManager);

        $handler = new FulfillRequestItemHandler(
            requestRepository:          $requestRepo,
            serializedStockRepository:  $serializedStockRepo,
            nonserializedStockRepository: $nonserializedStockRepo,
            movementRepository:         $movementRepo,
            eventPublisher:             $publisher,
            idGenerator:                $idGenerator,
            transactionManager:         $transactionManager,
        );

        ($handler)(new FulfillRequestItemCommand(
            requestId: self::REQUEST_ID,
            itemId:    self::ITEM_ID,
        ));

        self::assertSame(7, $sourceStock->quantity());
        self::assertCount(2, $publishedEvents);
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn(null);
        $requestRepo->expects($this->never())->method('save');

        $serializedStockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedStockRepo->expects($this->never())->method('findById');

        $nonserializedStockRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedStockRepo->expects($this->never())->method('findByProductAndLocation');

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->never())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $transactionManager->expects($this->once())->method('begin');
        $transactionManager->expects($this->never())->method('commit');
        $transactionManager->expects($this->once())->method('rollback');

        $handler = new FulfillRequestItemHandler(
            requestRepository:          $requestRepo,
            serializedStockRepository:  $serializedStockRepo,
            nonserializedStockRepository: $nonserializedStockRepo,
            movementRepository:         $movementRepo,
            eventPublisher:             $publisher,
            idGenerator:                $idGenerator,
            transactionManager:         $transactionManager,
        );

        $this->expectException(ApplicationException::class);

        ($handler)(new FulfillRequestItemCommand(
            requestId: self::REQUEST_ID,
            itemId:    self::ITEM_ID,
        ));
    }

    public function testThrowsWhenItemNotFoundInRequest(): void
    {
        $request = $this->makeRequest();

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);
        $requestRepo->expects($this->never())->method('save');

        $serializedStockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedStockRepo->expects($this->never())->method('findById');

        $nonserializedStockRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedStockRepo->expects($this->never())->method('findByProductAndLocation');

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->never())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $transactionManager->expects($this->once())->method('begin');
        $transactionManager->expects($this->never())->method('commit');
        $transactionManager->expects($this->once())->method('rollback');

        $handler = new FulfillRequestItemHandler(
            requestRepository:          $requestRepo,
            serializedStockRepository:  $serializedStockRepo,
            nonserializedStockRepository: $nonserializedStockRepo,
            movementRepository:         $movementRepo,
            eventPublisher:             $publisher,
            idGenerator:                $idGenerator,
            transactionManager:         $transactionManager,
        );

        $this->expectException(ApplicationException::class);

        ($handler)(new FulfillRequestItemCommand(
            requestId: self::REQUEST_ID,
            itemId:    self::ITEM_ID,
        ));
    }
}
