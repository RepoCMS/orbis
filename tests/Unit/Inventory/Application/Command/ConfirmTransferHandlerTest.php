<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\ConfirmTransfer\ConfirmTransferCommand;
use App\Inventory\Application\Command\ConfirmTransfer\ConfirmTransferHandler;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class ConfirmTransferHandlerTest extends TestCase
{
    private const MOVEMENT_ID = '01HZ0000000000000000000010';
    private const PRODUCT_ID = '01HZ0000000000000000000003';
    private const SOURCE_LOCATION_ID = '01HZ0000000000000000000002';
    private const DEST_LOCATION_ID = '01HZ0000000000000000000001';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000004';
    private const CONFIRMED_BY = '01HZ0000000000000000000050';

    private function makeMovement(
        ?SerializedStockIdValueObject $serializedStockId = null,
        int $quantity = 1,
    ): Movement {
        return Movement::create(
            id: new MovementIdValueObject(self::MOVEMENT_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            type: MovementType::TRANSFER,
            sourceLocationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            destinationLocationId: new LocationIdValueObject(self::DEST_LOCATION_ID),
            serializedStockId: $serializedStockId,
            quantity: $quantity,
        );
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

    public function testConfirmsSerializedTransfer(): void
    {
        $movement = $this->makeMovement(
            serializedStockId: new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
        );
        $serializedStock = $this->makeSerializedStock();

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);
        $movementRepo->expects($this->once())->method('save')->with($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($serializedStock);
        $serializedRepo->expects($this->once())->method('save')->with($serializedStock);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->never())->method('generate');

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');
        $txManager->expects($this->never())->method('rollback');

        $handler = new ConfirmTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        ($handler)(new ConfirmTransferCommand(
            movementId: self::MOVEMENT_ID,
            confirmedBy: self::CONFIRMED_BY,
        ));

        self::assertSame(MovementStatus::COMPLETED, $movement->status());
        self::assertSame(self::DEST_LOCATION_ID, (string) $serializedStock->locationId(), 'Serialized stock should be moved to destination');
        self::assertTrue($serializedStock->isAvailable(), 'Serialized stock should be available after transfer');
    }

    public function testConfirmsNonserializedTransferToExistingStock(): void
    {
        $movement = $this->makeMovement(serializedStockId: null, quantity: 3);
        $destStock = NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject('01HZ0000000000000000000090'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::DEST_LOCATION_ID),
            initialQuantity: 5,
        );

        $reservedStock = NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject('01HZ0000000000000000000091'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            initialQuantity: 3,
        );
        $reservedStock->reserve();

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);
        $movementRepo->expects($this->once())->method('save')->with($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocationAndStatus')
            ->with(
                $movement->productId(),
                $movement->sourceLocationId(),
                NonserializedStockStatus::RESERVED,
            )
            ->willReturn($reservedStock);
        $nonserializedRepo->expects($this->once())->method('remove')->with($reservedStock);
        $nonserializedRepo->method('findByProductAndLocation')
            ->with(
                $movement->productId(),
                $movement->destinationLocationId(),
            )
            ->willReturn($destStock);
        $nonserializedRepo->expects($this->once())->method('save')->with($destStock);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->never())->method('generate');

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new ConfirmTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        ($handler)(new ConfirmTransferCommand(
            movementId: self::MOVEMENT_ID,
            confirmedBy: self::CONFIRMED_BY,
        ));

        self::assertSame(MovementStatus::COMPLETED, $movement->status());
        self::assertSame(8, $destStock->quantity(), 'Destination stock should have increased from 5 to 8');
    }

    public function testConfirmsNonserializedTransferToNewLocation(): void
    {
        $movement = $this->makeMovement(serializedStockId: null, quantity: 3);
        $newStockId = '01HZ0000000000000000000099';

        $reservedStock = NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject('01HZ0000000000000000000091'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            initialQuantity: 3,
        );
        $reservedStock->reserve();

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);
        $movementRepo->expects($this->once())->method('save')->with($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn($reservedStock);
        $nonserializedRepo->expects($this->once())->method('remove')->with($reservedStock);
        $nonserializedRepo->method('findByProductAndLocation')->willReturn(null);

        $savedNewStock = null;
        $nonserializedRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (NonserializedStock $s) use (&$savedNewStock): void {
                $savedNewStock = $s;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn($newStockId);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new ConfirmTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        ($handler)(new ConfirmTransferCommand(
            movementId: self::MOVEMENT_ID,
            confirmedBy: self::CONFIRMED_BY,
        ));

        self::assertSame(MovementStatus::COMPLETED, $movement->status());
        self::assertNotNull($savedNewStock);
        self::assertSame($newStockId, (string) $savedNewStock->id());
        self::assertSame(self::DEST_LOCATION_ID, (string) $savedNewStock->locationId());
        self::assertSame(3, $savedNewStock->quantity());
        self::assertTrue($savedNewStock->isAvailable());
    }

    public function testThrowsWhenMovementNotFound(): void
    {
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn(null);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new ConfirmTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Movimiento no encontrado.');

        ($handler)(new ConfirmTransferCommand(
            movementId: self::MOVEMENT_ID,
            confirmedBy: self::CONFIRMED_BY,
        ));
    }

    public function testThrowsWhenMovementHasNoDestination(): void
    {
        $movement = Movement::create(
            id: new MovementIdValueObject(self::MOVEMENT_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            type: MovementType::ENTRY,
            sourceLocationId: null,
            destinationLocationId: null,
            serializedStockId: null,
            quantity: 1,
        );

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new ConfirmTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('El movimiento no tiene ubicación de destino.');

        ($handler)(new ConfirmTransferCommand(
            movementId: self::MOVEMENT_ID,
            confirmedBy: self::CONFIRMED_BY,
        ));
    }

    public function testThrowsWhenSerializedStockNotFound(): void
    {
        $movement = $this->makeMovement(
            serializedStockId: new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
        );

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn(null);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new ConfirmTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Stock serializado no encontrado.');

        ($handler)(new ConfirmTransferCommand(
            movementId: self::MOVEMENT_ID,
            confirmedBy: self::CONFIRMED_BY,
        ));
    }

    public function testHandlesNonserializedWithoutReservedStock(): void
    {
        $movement = $this->makeMovement(serializedStockId: null, quantity: 3);
        $destStock = NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject('01HZ0000000000000000000090'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::DEST_LOCATION_ID),
            initialQuantity: 5,
        );

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);
        $movementRepo->expects($this->once())->method('save');

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn(null);
        $nonserializedRepo->expects($this->never())->method('remove');
        $nonserializedRepo->method('findByProductAndLocation')->willReturn($destStock);
        $nonserializedRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new ConfirmTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        ($handler)(new ConfirmTransferCommand(
            movementId: self::MOVEMENT_ID,
            confirmedBy: self::CONFIRMED_BY,
        ));

        self::assertSame(MovementStatus::COMPLETED, $movement->status());
        self::assertSame(8, $destStock->quantity());
    }

    public function testRollsBackOnException(): void
    {
        $movement = $this->makeMovement(
            serializedStockId: new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
        );

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn(null);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new ConfirmTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);

        ($handler)(new ConfirmTransferCommand(
            movementId: self::MOVEMENT_ID,
            confirmedBy: self::CONFIRMED_BY,
        ));
    }
}
