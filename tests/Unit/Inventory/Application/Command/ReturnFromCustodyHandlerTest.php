<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\ReturnFromCustody\ReturnFromCustodyCommand;
use App\Inventory\Application\Command\ReturnFromCustody\ReturnFromCustodyHandler;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use PHPUnit\Framework\TestCase;

final class ReturnFromCustodyHandlerTest extends TestCase
{
    private const CUSTODY_LOCATION_ID = '01HZ0000000000000000000001';
    private const RETURN_LOCATION_ID = '01HZ0000000000000000000002';
    private const STOCK_ITEM_ID = '01HZ0000000000000000000003';
    private const MOVEMENT_ID = '01HZ0000000000000000000004';
    private const PRODUCT_ID = '01HZ0000000000000000000005';
    private const LOCATION_ID = '01HZ0000000000000000000006';

    private function makeCustodyLocation(): Location
    {
        return Location::create(
            id: new LocationIdValueObject(self::CUSTODY_LOCATION_ID),
            name: new LocationNameValueObject('Custody - Employee'),
            city: 'City',
            address: 'Address',
            type: LocationType::CUSTODY,
        );
    }

    private function makeReturnLocation(): Location
    {
        return Location::create(
            id: new LocationIdValueObject(self::RETURN_LOCATION_ID),
            name: new LocationNameValueObject('Warehouse'),
            city: 'City',
            address: 'Address',
            type: LocationType::WAREHOUSE,
        );
    }

    private function makeStockAtCustody(): SerializedStock
    {
        return SerializedStock::register(
            id: new SerializedStockIdValueObject(self::STOCK_ITEM_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject('SN-CUSTODY-001'),
            locationId: new LocationIdValueObject(self::CUSTODY_LOCATION_ID),
        );
    }

    public function testReturnsFromCustody(): void
    {
        $custodyLocation = $this->makeCustodyLocation();
        $returnLocation = $this->makeReturnLocation();
        $stockItem = $this->makeStockAtCustody();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturnCallback(
            fn(LocationIdValueObject $id) => match ((string) $id) {
                self::CUSTODY_LOCATION_ID => $custodyLocation,
                self::RETURN_LOCATION_ID => $returnLocation,
                default => null,
            }
        );

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($stockItem);
        $serializedRepo->expects($this->once())->method('save')->with($stockItem);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');
        $txManager->expects($this->never())->method('rollback');

        $handler = new ReturnFromCustodyHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        ($handler)(new ReturnFromCustodyCommand(
            custodyLocationId: self::CUSTODY_LOCATION_ID,
            stockItemId: self::STOCK_ITEM_ID,
            returnLocationId: self::RETURN_LOCATION_ID,
        ));

        self::assertCount(1, $savedMovements);
        $movement = $savedMovements[0];
        self::assertSame(MovementType::TRANSFER, $movement->type());
        self::assertSame(self::CUSTODY_LOCATION_ID, (string) $movement->sourceLocationId());
        self::assertSame(self::RETURN_LOCATION_ID, (string) $movement->destinationLocationId());
        self::assertTrue($movement->isCompleted());

        self::assertSame(self::RETURN_LOCATION_ID, (string) $stockItem->locationId());
        self::assertTrue($stockItem->isAvailable());
    }

    public function testThrowsWhenCustodyLocationNotFound(): void
    {
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn(null);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new ReturnFromCustodyHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud');

        ($handler)(new ReturnFromCustodyCommand(
            custodyLocationId: self::CUSTODY_LOCATION_ID,
            stockItemId: self::STOCK_ITEM_ID,
            returnLocationId: self::RETURN_LOCATION_ID,
        ));
    }

    public function testThrowsWhenReturnLocationNotFound(): void
    {
        $custodyLocation = $this->makeCustodyLocation();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturnCallback(
            fn(LocationIdValueObject $id) => match ((string) $id) {
                self::CUSTODY_LOCATION_ID => $custodyLocation,
                default => null,
            }
        );

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new ReturnFromCustodyHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud');

        ($handler)(new ReturnFromCustodyCommand(
            custodyLocationId: self::CUSTODY_LOCATION_ID,
            stockItemId: self::STOCK_ITEM_ID,
            returnLocationId: self::RETURN_LOCATION_ID,
        ));
    }

    public function testThrowsWhenStockNotFound(): void
    {
        $custodyLocation = $this->makeCustodyLocation();
        $returnLocation = $this->makeReturnLocation();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturnCallback(
            fn(LocationIdValueObject $id) => match ((string) $id) {
                self::CUSTODY_LOCATION_ID => $custodyLocation,
                self::RETURN_LOCATION_ID => $returnLocation,
                default => null,
            }
        );

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn(null);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new ReturnFromCustodyHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud');

        ($handler)(new ReturnFromCustodyCommand(
            custodyLocationId: self::CUSTODY_LOCATION_ID,
            stockItemId: self::STOCK_ITEM_ID,
            returnLocationId: self::RETURN_LOCATION_ID,
        ));
    }

    public function testThrowsWhenLocationNotCustody(): void
    {
        $nonCustody = Location::create(
            id: new LocationIdValueObject(self::CUSTODY_LOCATION_ID),
            name: new LocationNameValueObject('Warehouse'),
            city: 'City',
            address: 'Address',
            type: LocationType::WAREHOUSE,
        );

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($nonCustody);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new ReturnFromCustodyHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud');

        ($handler)(new ReturnFromCustodyCommand(
            custodyLocationId: self::CUSTODY_LOCATION_ID,
            stockItemId: self::STOCK_ITEM_ID,
            returnLocationId: self::RETURN_LOCATION_ID,
        ));
    }

    public function testThrowsWhenStockNotAtCustodyLocation(): void
    {
        $custodyLocation = $this->makeCustodyLocation();
        $returnLocation = $this->makeReturnLocation();

        $stockItem = SerializedStock::register(
            id: new SerializedStockIdValueObject(self::STOCK_ITEM_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject('SN-WRONG-001'),
            locationId: new LocationIdValueObject(self::LOCATION_ID),
        );

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturnCallback(
            fn(LocationIdValueObject $id) => match ((string) $id) {
                self::CUSTODY_LOCATION_ID => $custodyLocation,
                self::RETURN_LOCATION_ID => $returnLocation,
                default => null,
            }
        );

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($stockItem);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new ReturnFromCustodyHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud');

        ($handler)(new ReturnFromCustodyCommand(
            custodyLocationId: self::CUSTODY_LOCATION_ID,
            stockItemId: self::STOCK_ITEM_ID,
            returnLocationId: self::RETURN_LOCATION_ID,
        ));
    }
}
