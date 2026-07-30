<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\CreateCustodyAssignment\CreateCustodyAssignmentCommand;
use App\Inventory\Application\Command\CreateCustodyAssignment\CreateCustodyAssignmentHandler;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CreateCustodyAssignmentHandlerTest extends TestCase
{
    private const EMPLOYEE_ID = '01HJH7XMJ7KJ9WY6X8Z0M3N4P5';
    private const STOCK_ITEM_ID = '01HZ0000000000000000000001';
    private const SOURCE_LOCATION_ID = '01HZ0000000000000000000002';
    private const CUSTODY_LOCATION_ID = '01HZ0000000000000000000003';
    private const MOVEMENT_ID = '01HZ0000000000000000000004';
    private const PRODUCT_ID = '01HZ0000000000000000000005';
    private const NOTES = 'Test custody assignment';

    private function makeActiveEmployee(): Employee
    {
        return Employee::hire(
            id: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('12345678'),
            firstName: 'Juan Carlos',
            lastName: 'Pérez Gómez',
            email: new EmployeeEmailValueObject('juan@example.com'),
            phoneNumber: '0998880000',
            hiredAt: new DateTimeImmutable(),
            status: EmployeeStatus::ACTIVE,
            userId: null,
            profile: EmployeeProfile::ADMINISTRATIVE,
        );
    }

    private function makeInactiveEmployee(): Employee
    {
        $employee = Employee::hire(
            id: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('87654321'),
            firstName: 'Pedro Antonio',
            lastName: 'Gómez Ruiz',
            email: new EmployeeEmailValueObject('pedro@example.com'),
            phoneNumber: '0998881111',
            hiredAt: new DateTimeImmutable('2024-01-01'),
            userId: null,
            profile: EmployeeProfile::ADMINISTRATIVE,
        );
        $employee->terminate();

        return $employee;
    }

    private function makeSourceLocation(): Location
    {
        return Location::create(
            id: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            name: new LocationNameValueObject('Warehouse'),
            city: 'City',
            address: 'Address',
            type: LocationType::WAREHOUSE,
        );
    }

    public function testCreatesCustodyAssignment(): void
    {
        $employee = $this->makeActiveEmployee();
        $sourceLocation = $this->makeSourceLocation();

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($sourceLocation);
        $locationRepo->method('findByEmployeeId')->willReturn([]);

        $stockItem = SerializedStock::register(
            id: new SerializedStockIdValueObject(self::STOCK_ITEM_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject('SN-CUST-001'),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
        );

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($stockItem);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(self::CUSTODY_LOCATION_ID, self::MOVEMENT_ID);

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');
        $txManager->expects($this->never())->method('rollback');

        $handler = new CreateCustodyAssignmentHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            employeeRepository: $employeeRepo,
            transactionManager: $txManager,
            idempotencyService: $idempotency,
        );

        ($handler)(new CreateCustodyAssignmentCommand(
            employeeId: self::EMPLOYEE_ID,
            stockItemId: self::STOCK_ITEM_ID,
            locationId: self::SOURCE_LOCATION_ID,
            notes: self::NOTES,
        ));

        self::assertCount(1, $savedMovements);
        $movement = $savedMovements[0];
        self::assertSame(MovementType::TRANSFER, $movement->type());
        self::assertSame(self::SOURCE_LOCATION_ID, (string) $movement->sourceLocationId());
        self::assertSame(self::NOTES, $movement->notes());
        self::assertTrue($stockItem->isReserved(), 'Stock should be reserved after custody assignment');
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn(null);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new CreateCustodyAssignmentHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            employeeRepository: $employeeRepo,
            transactionManager: $txManager,
            idempotencyService: $idempotency,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud');

        ($handler)(new CreateCustodyAssignmentCommand(
            employeeId: self::EMPLOYEE_ID,
            stockItemId: self::STOCK_ITEM_ID,
            locationId: self::SOURCE_LOCATION_ID,
        ));
    }

    public function testThrowsWhenEmployeeNotActive(): void
    {
        $employee = $this->makeInactiveEmployee();

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new CreateCustodyAssignmentHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            employeeRepository: $employeeRepo,
            transactionManager: $txManager,
            idempotencyService: $idempotency,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud');

        ($handler)(new CreateCustodyAssignmentCommand(
            employeeId: self::EMPLOYEE_ID,
            stockItemId: self::STOCK_ITEM_ID,
            locationId: self::SOURCE_LOCATION_ID,
        ));
    }

    public function testThrowsWhenLocationNotFound(): void
    {
        $employee = $this->makeActiveEmployee();

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn(null);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new CreateCustodyAssignmentHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            employeeRepository: $employeeRepo,
            transactionManager: $txManager,
            idempotencyService: $idempotency,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud');

        ($handler)(new CreateCustodyAssignmentCommand(
            employeeId: self::EMPLOYEE_ID,
            stockItemId: self::STOCK_ITEM_ID,
            locationId: self::SOURCE_LOCATION_ID,
        ));
    }

    public function testThrowsWhenStockNotFound(): void
    {
        $employee = $this->makeActiveEmployee();
        $sourceLocation = $this->makeSourceLocation();

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($sourceLocation);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn(null);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new CreateCustodyAssignmentHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            employeeRepository: $employeeRepo,
            transactionManager: $txManager,
            idempotencyService: $idempotency,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud');

        ($handler)(new CreateCustodyAssignmentCommand(
            employeeId: self::EMPLOYEE_ID,
            stockItemId: self::STOCK_ITEM_ID,
            locationId: self::SOURCE_LOCATION_ID,
        ));
    }

    public function testUsesExistingCustodyLocation(): void
    {
        $employee = $this->makeActiveEmployee();
        $sourceLocation = $this->makeSourceLocation();

        $existingCustody = Location::create(
            id: new LocationIdValueObject(self::CUSTODY_LOCATION_ID),
            name: new LocationNameValueObject('Custody Location'),
            city: 'City',
            address: 'Address',
            type: LocationType::CUSTODY,
        );

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($sourceLocation);
        $locationRepo->method('findByEmployeeId')->willReturn([$existingCustody]);

        $stockItem = SerializedStock::register(
            id: new SerializedStockIdValueObject(self::STOCK_ITEM_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject('SN-CUST-002'),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
        );

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($stockItem);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new CreateCustodyAssignmentHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            employeeRepository: $employeeRepo,
            transactionManager: $txManager,
            idempotencyService: $idempotency,
        );

        ($handler)(new CreateCustodyAssignmentCommand(
            employeeId: self::EMPLOYEE_ID,
            stockItemId: self::STOCK_ITEM_ID,
            locationId: self::SOURCE_LOCATION_ID,
        ));

        self::assertSame(self::CUSTODY_LOCATION_ID, (string) $existingCustody->id());
    }

    public function testThrowsWhenEmployeeAlreadyHasOtherLocation(): void
    {
        $employee = $this->makeActiveEmployee();
        $sourceLocation = $this->makeSourceLocation();

        $otherLocation = Location::create(
            id: new LocationIdValueObject('01HZ0000000000000000000099'),
            name: new LocationNameValueObject('Backpack Location'),
            city: 'City',
            address: 'Address',
            type: LocationType::BACKPACK,
        );

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($sourceLocation);
        $locationRepo->method('findByEmployeeId')->willReturn([$otherLocation]);

        $stockItem = SerializedStock::register(
            id: new SerializedStockIdValueObject(self::STOCK_ITEM_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject('SN-CUST-003'),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
        );

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($stockItem);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new CreateCustodyAssignmentHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            employeeRepository: $employeeRepo,
            transactionManager: $txManager,
            idempotencyService: $idempotency,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud');

        ($handler)(new CreateCustodyAssignmentCommand(
            employeeId: self::EMPLOYEE_ID,
            stockItemId: self::STOCK_ITEM_ID,
            locationId: self::SOURCE_LOCATION_ID,
        ));
    }

    public function testRollsBackOnException(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn(null);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new CreateCustodyAssignmentHandler(
            locationRepository: $locationRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            employeeRepository: $employeeRepo,
            transactionManager: $txManager,
            idempotencyService: $idempotency,
        );

        $this->expectException(ApplicationException::class);

        ($handler)(new CreateCustodyAssignmentCommand(
            employeeId: self::EMPLOYEE_ID,
            stockItemId: self::STOCK_ITEM_ID,
            locationId: self::SOURCE_LOCATION_ID,
        ));
    }
}
