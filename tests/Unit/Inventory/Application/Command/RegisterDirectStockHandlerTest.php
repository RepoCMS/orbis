<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\RegisterDirectStock\RegisterDirectStockCommand;
use App\Inventory\Application\Command\RegisterDirectStock\RegisterDirectStockHandler;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RegisterDirectStockHandlerTest extends TestCase
{
    private const PRODUCT_ID = '01HZ0000000000000000000001';
    private const LOCATION_ID = '01HZ0000000000000000000002';
    private const SERIALIZED_ID = '01HZ0000000000000000000003';
    private const MOVEMENT_ID = '01HZ0000000000000000000004';
    private const NON_SERIALIZED_ID = '01HZ0000000000000000000005';
    private const SERIAL_NUMBER = 'SN-REG-001';
    private const CREATED_BY = '01HZ0000000000000000000010';

    private function makeLocation(): Location
    {
        return Location::create(
            new LocationIdValueObject(self::LOCATION_ID),
            new LocationNameValueObject('Warehouse'),
            'City',
            'Address',
            LocationType::WAREHOUSE,
        );
    }

    private function makeSerializedProduct(): Product
    {
        return Product::add(
            new ProductIdValueObject(self::PRODUCT_ID),
            new ProductCodeAssignedBySupplierValueObject('ATM-001'),
            new ProductNameValueObject('ATM Display'),
            'Serialized product',
            ProductTrackingType::SERIALIZED,
        );
    }

    private function makeNonserializedProduct(): Product
    {
        return Product::add(
            new ProductIdValueObject(self::PRODUCT_ID),
            new ProductCodeAssignedBySupplierValueObject('PAPER-001'),
            new ProductNameValueObject('Thermal Paper'),
            'Non-serialized product',
            ProductTrackingType::NON_SERIALIZED,
        );
    }

    public function testRegistersSerializedStock(): void
    {
        $product = $this->makeSerializedProduct();
        $location = $this->makeLocation();

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(self::SERIALIZED_ID, self::MOVEMENT_ID);

        $savedSerialized = null;
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (SerializedStock $s) use (&$savedSerialized): void {
                $savedSerialized = $s;
            });

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $handler = new RegisterDirectStockHandler(
            productRepository: $productRepo,
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new RegisterDirectStockCommand(
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: self::SERIAL_NUMBER,
            quantity: 1,
            registeredById: self::CREATED_BY,
        ));

        self::assertNotNull($savedSerialized);
        self::assertSame(self::SERIALIZED_ID, (string) $savedSerialized->id());
        self::assertSame(self::SERIAL_NUMBER, (string) $savedSerialized->serialNumber());

        self::assertCount(1, $savedMovements);
        $movement = $savedMovements[0];
        self::assertSame(MovementType::ENTRY, $movement->type());
        self::assertTrue($movement->isCompleted());
        self::assertSame(self::SERIALIZED_ID, (string) $movement->serializedStockId());
    }

    public function testRegistersNonserializedStock(): void
    {
        $product = $this->makeNonserializedProduct();
        $location = $this->makeLocation();

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(self::NON_SERIALIZED_ID, self::MOVEMENT_ID);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocation')->willReturn(null);

        $savedNonserialized = null;
        $nonserializedRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (NonserializedStock $s) use (&$savedNonserialized): void {
                $savedNonserialized = $s;
            });

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $handler = new RegisterDirectStockHandler(
            productRepository: $productRepo,
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new RegisterDirectStockCommand(
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: null,
            quantity: 5,
            registeredById: self::CREATED_BY,
        ));

        self::assertNotNull($savedNonserialized);
        self::assertSame(5, $savedNonserialized->quantity());
        self::assertTrue($savedNonserialized->isAvailable());

        self::assertCount(1, $savedMovements);
        $movement = $savedMovements[0];
        self::assertSame(MovementType::ENTRY, $movement->type());
        self::assertSame(5, $movement->quantity());
        self::assertNull($movement->serializedStockId());
    }

    public function testRegistersNonserializedStockWithExistingBatch(): void
    {
        $product = $this->makeNonserializedProduct();
        $location = $this->makeLocation();
        $existing = NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject(self::NON_SERIALIZED_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::LOCATION_ID),
            initialQuantity: 10,
        );

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocation')->willReturn($existing);

        $nonserializedRepo->expects($this->once())
            ->method('save')
            ->with($existing);

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $handler = new RegisterDirectStockHandler(
            productRepository: $productRepo,
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new RegisterDirectStockCommand(
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: null,
            quantity: 3,
            registeredById: self::CREATED_BY,
        ));

        self::assertSame(13, $existing->quantity(), 'Existing batch should be increased by 3');
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn(null);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);

        $handler = new RegisterDirectStockHandler(
            productRepository: $productRepo,
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(ProductNotFoundException::class);

        ($handler)(new RegisterDirectStockCommand(
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: null,
            quantity: 1,
            registeredById: self::CREATED_BY,
        ));
    }

    public function testThrowsWhenLocationNotFound(): void
    {
        $product = $this->makeNonserializedProduct();

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn(null);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);

        $handler = new RegisterDirectStockHandler(
            productRepository: $productRepo,
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(LocationNotFoundException::class);

        ($handler)(new RegisterDirectStockCommand(
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: null,
            quantity: 1,
            registeredById: self::CREATED_BY,
        ));
    }

    public function testThrowsWhenSerialNumberGivenForNonserializedProduct(): void
    {
        $product = $this->makeNonserializedProduct();
        $location = $this->makeLocation();

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);

        $handler = new RegisterDirectStockHandler(
            productRepository: $productRepo,
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('no serializado');

        ($handler)(new RegisterDirectStockCommand(
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: 'SN-BAD',
            quantity: 1,
            registeredById: self::CREATED_BY,
        ));
    }

    public function testThrowsWhenSerialNumberMissingForSerializedProduct(): void
    {
        $product = $this->makeSerializedProduct();
        $location = $this->makeLocation();

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);

        $handler = new RegisterDirectStockHandler(
            productRepository: $productRepo,
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('serializado');

        ($handler)(new RegisterDirectStockCommand(
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: null,
            quantity: 1,
            registeredById: self::CREATED_BY,
        ));
    }
}
