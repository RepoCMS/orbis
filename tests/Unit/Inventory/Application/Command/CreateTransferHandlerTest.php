<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\CreateTransfer\CreateTransferCommand;
use App\Inventory\Application\Command\CreateTransfer\CreateTransferHandler;
use App\Inventory\Application\Service\TransferAuthorizationService;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Exceptions\InsufficientStockException;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CreateTransferHandlerTest extends TestCase
{
    private const DEST_LOCATION_ID = '01HZ0000000000000000000001';
    private const SOURCE_LOCATION_ID = '01HZ0000000000000000000002';
    private const PRODUCT_ID = '01HZ0000000000000000000003';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000004';
    private const NON_SERIALIZED_STOCK_ID = '01HZ0000000000000000000005';
    private const MOVEMENT_ID = '01HZ0000000000000000000010';
    private const CREATED_BY = '01HZ0000000000000000000020';
    private const EMPLOYEE_ID = '01HJH7XMJ7KJ9WY6X8Z0M3N4P5';
    private const NOTES = 'Test transfer';

    private function makeDestinationLocation(): Location
    {
        $location = Location::create(
            id: new LocationIdValueObject(self::DEST_LOCATION_ID),
            name: new LocationNameValueObject('Warehouse'),
            city: 'City',
            address: 'Address',
            type: LocationType::WAREHOUSE,
        );
        $location->update('Warehouse', 'City', 'Address', self::EMPLOYEE_ID);

        return $location;
    }

    private function makeSerializedStock(): SerializedStock
    {
        return SerializedStock::register(
            id: new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject('SN-001'),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
        );
    }

    private function makeNonserializedStock(int $quantity = 10): NonserializedStock
    {
        return NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject(self::NON_SERIALIZED_STOCK_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            initialQuantity: $quantity,
        );
    }

    private function makeNonTechnicianEmployee(): Employee
    {
        return Employee::hire(
            id: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('12345678'),
            firstName: 'Admin',
            lastName: 'Staff',
            email: new EmployeeEmailValueObject('admin@example.com'),
            phoneNumber: '0998880000',
            hiredAt: new DateTimeImmutable(),
            status: EmployeeStatus::ACTIVE,
            userId: null,
            profile: EmployeeProfile::ADMINISTRATIVE,
        );
    }

    public function testInitiatesSerializedTransfer(): void
    {
        $destLocation = $this->makeDestinationLocation();
        $serializedStock = $this->makeSerializedStock();
        $employee = $this->makeNonTechnicianEmployee();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($destLocation);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($serializedStock);
        $serializedRepo->expects($this->once())->method('save')->with($serializedStock);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);
        $employeeRepo->method('findById')->willReturn($employee);

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

        $transferAuth = new TransferAuthorizationService($employeeRepo, $locationRepo);

        $handler = new CreateTransferHandler(
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            locationRepository: $locationRepo,
            movementRepository: $movementRepo,
            employeeRepository: $employeeRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
            transferAuth: $transferAuth,
        );

        ($handler)(new CreateTransferCommand(
            destinationLocationId: self::DEST_LOCATION_ID,
            items: [
                [
                    'stockId' => self::SERIALIZED_STOCK_ID,
                    'type' => 'serialized',
                    'productId' => self::PRODUCT_ID,
                ],
            ],
            notes: self::NOTES,
            createdBy: self::CREATED_BY,
        ));

        self::assertCount(1, $savedMovements);
        $movement = $savedMovements[0];
        self::assertSame(self::MOVEMENT_ID, (string) $movement->id());
        self::assertSame(self::PRODUCT_ID, (string) $movement->productId());
        self::assertSame(MovementType::TRANSFER, $movement->type());
        self::assertSame(MovementStatus::IN_TRANSIT, $movement->status());
        self::assertSame(self::SOURCE_LOCATION_ID, (string) $movement->sourceLocationId());
        self::assertSame(self::DEST_LOCATION_ID, (string) $movement->destinationLocationId());
        self::assertSame(1, $movement->quantity());
        self::assertSame(self::NOTES, $movement->notes());
        self::assertSame(self::CREATED_BY, $movement->createdByUserId());
        self::assertTrue($serializedStock->isReserved(), 'Serialized stock should be reserved after transfer initiation');
    }

    public function testInitiatesNonserializedTransferWithoutExistingReserved(): void
    {
        $destLocation = $this->makeDestinationLocation();
        $sourceStock = $this->makeNonserializedStock(10);
        $employee = $this->makeNonTechnicianEmployee();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($destLocation);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findById')->willReturn($sourceStock);
        $nonserializedRepo->method('hardDeleteByProductAndLocationAndStatus');
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn(null);

        $savedNonserialized = [];
        $nonserializedRepo->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function (NonserializedStock $s) use (&$savedNonserialized): void {
                $savedNonserialized[] = $s;
            });

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);
        $employeeRepo->method('findById')->willReturn($employee);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->exactly(2))->method('generate')->willReturn(self::MOVEMENT_ID, '01HZ0000000000000000000099');

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

        $transferAuth = new TransferAuthorizationService($employeeRepo, $locationRepo);

        $handler = new CreateTransferHandler(
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            locationRepository: $locationRepo,
            movementRepository: $movementRepo,
            employeeRepository: $employeeRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
            transferAuth: $transferAuth,
        );

        ($handler)(new CreateTransferCommand(
            destinationLocationId: self::DEST_LOCATION_ID,
            items: [
                [
                    'stockId' => self::NON_SERIALIZED_STOCK_ID,
                    'type' => 'nonserialized',
                    'productId' => self::PRODUCT_ID,
                    'quantity' => 3,
                ],
            ],
            notes: self::NOTES,
            createdBy: self::CREATED_BY,
        ));

        self::assertCount(1, $savedMovements);
        $movement = $savedMovements[0];
        self::assertSame(MovementStatus::IN_TRANSIT, $movement->status());
        self::assertSame(3, $movement->quantity());
        self::assertNull($movement->serializedStockId());

        self::assertCount(2, $savedNonserialized);
        $reserved = $savedNonserialized[0];
        $available = $savedNonserialized[1];
        self::assertSame(NonserializedStockStatus::RESERVED, $reserved->status());
        self::assertSame(NonserializedStockStatus::AVAILABLE, $available->status());
    }

    public function testInitiatesNonserializedTransferExactQuantity(): void
    {
        $destLocation = $this->makeDestinationLocation();
        $sourceStock = $this->makeNonserializedStock(3);
        $employee = $this->makeNonTechnicianEmployee();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($destLocation);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findById')->willReturn($sourceStock);
        $nonserializedRepo->method('hardDeleteByProductAndLocationAndStatus');
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn(null);

        $nonserializedRepo->expects($this->once())->method('save');

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);
        $employeeRepo->method('findById')->willReturn($employee);

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

        $transferAuth = new TransferAuthorizationService($employeeRepo, $locationRepo);

        $handler = new CreateTransferHandler(
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            locationRepository: $locationRepo,
            movementRepository: $movementRepo,
            employeeRepository: $employeeRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
            transferAuth: $transferAuth,
        );

        ($handler)(new CreateTransferCommand(
            destinationLocationId: self::DEST_LOCATION_ID,
            items: [
                [
                    'stockId' => self::NON_SERIALIZED_STOCK_ID,
                    'type' => 'nonserialized',
                    'productId' => self::PRODUCT_ID,
                    'quantity' => 3,
                ],
            ],
            createdBy: self::CREATED_BY,
        ));

        self::assertCount(1, $savedMovements);
        $movement = $savedMovements[0];
        self::assertSame(MovementStatus::IN_TRANSIT, $movement->status());
        self::assertSame(3, $movement->quantity());
        self::assertNull($movement->serializedStockId());

        self::assertTrue($sourceStock->isReserved(), 'Source stock should be fully reserved');
        self::assertSame(3, $sourceStock->quantity());
    }

    public function testInitiatesNonserializedTransferWithExistingReserved(): void
    {
        $destLocation = $this->makeDestinationLocation();
        $sourceStock = $this->makeNonserializedStock(10);

        $existingReserved = NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject('01HZ0000000000000000000099'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            initialQuantity: 2,
        );
        $existingReserved->reserve();

        $employee = $this->makeNonTechnicianEmployee();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($destLocation);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findById')->willReturn($sourceStock);
        $nonserializedRepo->method('hardDeleteByProductAndLocationAndStatus');
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn($existingReserved);

        $saved = [];
        $nonserializedRepo->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function (NonserializedStock $s) use (&$saved): void {
                $saved[] = $s;
            });

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);
        $employeeRepo->method('findById')->willReturn($employee);

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

        $transferAuth = new TransferAuthorizationService($employeeRepo, $locationRepo);

        $handler = new CreateTransferHandler(
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            locationRepository: $locationRepo,
            movementRepository: $movementRepo,
            employeeRepository: $employeeRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
            transferAuth: $transferAuth,
        );

        ($handler)(new CreateTransferCommand(
            destinationLocationId: self::DEST_LOCATION_ID,
            items: [
                [
                    'stockId' => self::NON_SERIALIZED_STOCK_ID,
                    'type' => 'nonserialized',
                    'productId' => self::PRODUCT_ID,
                    'quantity' => 3,
                ],
            ],
            createdBy: self::CREATED_BY,
        ));

        self::assertCount(1, $savedMovements);
        $movement = $savedMovements[0];
        self::assertSame(3, $movement->quantity());

        self::assertCount(2, $saved);
        self::assertSame(5, $existingReserved->quantity(), 'Existing reserved should be increased by 3 (from 2 to 5)');
        self::assertSame(7, $sourceStock->quantity(), 'Source stock should be reduced by 3 (from 10 to 7)');
    }

    public function testThrowsWhenDestinationLocationNotFound(): void
    {
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn(null);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $transferAuth = new TransferAuthorizationService($employeeRepo, $locationRepo);

        $handler = new CreateTransferHandler(
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            locationRepository: $locationRepo,
            movementRepository: $movementRepo,
            employeeRepository: $employeeRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
            transferAuth: $transferAuth,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('La ubicación de destino no existe.');

        ($handler)(new CreateTransferCommand(
            destinationLocationId: '01HZ0000000000000000000099',
            items: [
                [
                    'stockId' => self::SERIALIZED_STOCK_ID,
                    'type' => 'serialized',
                    'productId' => self::PRODUCT_ID,
                ],
            ],
            createdBy: self::CREATED_BY,
        ));
    }

    public function testThrowsWhenItemsEmpty(): void
    {
        $destLocation = $this->makeDestinationLocation();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($destLocation);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $transferAuth = new TransferAuthorizationService($employeeRepo, $locationRepo);

        $handler = new CreateTransferHandler(
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            locationRepository: $locationRepo,
            movementRepository: $movementRepo,
            employeeRepository: $employeeRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
            transferAuth: $transferAuth,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Debe seleccionar al menos un item para transferir.');

        ($handler)(new CreateTransferCommand(
            destinationLocationId: self::DEST_LOCATION_ID,
            items: [],
            createdBy: self::CREATED_BY,
        ));
    }

    public function testThrowsWhenSerializedStockNotFound(): void
    {
        $destLocation = $this->makeDestinationLocation();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($destLocation);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn(null);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $transferAuth = new TransferAuthorizationService($employeeRepo, $locationRepo);

        $handler = new CreateTransferHandler(
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            locationRepository: $locationRepo,
            movementRepository: $movementRepo,
            employeeRepository: $employeeRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
            transferAuth: $transferAuth,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Item ' . self::SERIALIZED_STOCK_ID . ' no encontrado.');

        ($handler)(new CreateTransferCommand(
            destinationLocationId: self::DEST_LOCATION_ID,
            items: [
                [
                    'stockId' => self::SERIALIZED_STOCK_ID,
                    'type' => 'serialized',
                    'productId' => self::PRODUCT_ID,
                ],
            ],
            createdBy: self::CREATED_BY,
        ));
    }

    public function testThrowsWhenNonserializedStockNotAvailable(): void
    {
        $destLocation = $this->makeDestinationLocation();
        $unavailableStock = $this->makeNonserializedStock(5);
        $unavailableStock->reserve();
        $employee = $this->makeNonTechnicianEmployee();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($destLocation);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findById')->willReturn($unavailableStock);
        $nonserializedRepo->method('hardDeleteByProductAndLocationAndStatus');
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn(null);

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $transferAuth = new TransferAuthorizationService($employeeRepo, $locationRepo);

        $handler = new CreateTransferHandler(
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            locationRepository: $locationRepo,
            movementRepository: $movementRepo,
            employeeRepository: $employeeRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
            transferAuth: $transferAuth,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Stock no disponible en la ubicación de origen.');

        ($handler)(new CreateTransferCommand(
            destinationLocationId: self::DEST_LOCATION_ID,
            items: [
                [
                    'stockId' => self::NON_SERIALIZED_STOCK_ID,
                    'type' => 'nonserialized',
                    'productId' => self::PRODUCT_ID,
                    'quantity' => 3,
                ],
            ],
            createdBy: self::CREATED_BY,
        ));
    }

    public function testThrowsWhenInsufficientStock(): void
    {
        $destLocation = $this->makeDestinationLocation();
        $lowStock = $this->makeNonserializedStock(2);
        $employee = $this->makeNonTechnicianEmployee();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($destLocation);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findById')->willReturn($lowStock);
        $nonserializedRepo->method('hardDeleteByProductAndLocationAndStatus');
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn(null);

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $transferAuth = new TransferAuthorizationService($employeeRepo, $locationRepo);

        $handler = new CreateTransferHandler(
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            locationRepository: $locationRepo,
            movementRepository: $movementRepo,
            employeeRepository: $employeeRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
            transferAuth: $transferAuth,
        );

        $this->expectException(InsufficientStockException::class);

        ($handler)(new CreateTransferCommand(
            destinationLocationId: self::DEST_LOCATION_ID,
            items: [
                [
                    'stockId' => self::NON_SERIALIZED_STOCK_ID,
                    'type' => 'nonserialized',
                    'productId' => self::PRODUCT_ID,
                    'quantity' => 5,
                ],
            ],
            createdBy: self::CREATED_BY,
        ));
    }

    public function testRollsBackOnException(): void
    {
        $destLocation = $this->makeDestinationLocation();
        $unavailableStock = $this->makeNonserializedStock(5);
        $unavailableStock->reserve();
        $employee = $this->makeNonTechnicianEmployee();

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($destLocation);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findById')->willReturn($unavailableStock);
        $nonserializedRepo->method('hardDeleteByProductAndLocationAndStatus');
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn(null);

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $transferAuth = new TransferAuthorizationService($employeeRepo, $locationRepo);

        $handler = new CreateTransferHandler(
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            locationRepository: $locationRepo,
            movementRepository: $movementRepo,
            employeeRepository: $employeeRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
            transferAuth: $transferAuth,
        );

        $this->expectException(InventoryDomainException::class);

        ($handler)(new CreateTransferCommand(
            destinationLocationId: self::DEST_LOCATION_ID,
            items: [
                [
                    'stockId' => self::NON_SERIALIZED_STOCK_ID,
                    'type' => 'nonserialized',
                    'productId' => self::PRODUCT_ID,
                    'quantity' => 3,
                ],
            ],
            createdBy: self::CREATED_BY,
        ));
    }
}
