<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\CancelTransfer\CancelTransferCommand;
use App\Inventory\Application\Command\CancelTransfer\CancelTransferHandler;
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
use PHPUnit\Framework\TestCase;

final class CancelTransferHandlerTest extends TestCase
{
    private const MOVEMENT_ID = '01HZ0000000000000000000010';
    private const PRODUCT_ID = '01HZ0000000000000000000003';
    private const SOURCE_LOCATION_ID = '01HZ0000000000000000000002';
    private const DEST_LOCATION_ID = '01HZ0000000000000000000001';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000004';
    private const NON_SERIALIZED_STOCK_ID = '01HZ0000000000000000000005';
    private const CANCELLED_BY = '01HZ0000000000000000000060';

    private function makeInTransitMovement(
        ?SerializedStockIdValueObject $serializedStockId = null,
        int $quantity = 1,
    ): Movement {
        $movement = Movement::create(
            id: new MovementIdValueObject(self::MOVEMENT_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            type: MovementType::TRANSFER,
            sourceLocationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            destinationLocationId: new LocationIdValueObject(self::DEST_LOCATION_ID),
            serializedStockId: $serializedStockId,
            quantity: $quantity,
        );
        $movement->startTransit();
        return $movement;
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

    private function makeNonserializedStock(int $quantity = 5): NonserializedStock
    {
        return NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject(self::NON_SERIALIZED_STOCK_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            initialQuantity: $quantity,
        );
    }

    public function testCancelsSerializedTransfer(): void
    {
        $movement = $this->makeInTransitMovement(
            serializedStockId: new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
        );
        $serializedStock = $this->makeSerializedStock();
        $serializedStock->reserve();

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);
        $movementRepo->expects($this->once())->method('save')->with($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($serializedStock);
        $serializedRepo->expects($this->once())->method('save')->with($serializedStock);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');
        $txManager->expects($this->never())->method('rollback');

        $handler = new CancelTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            transactionManager: $txManager,
        );

        ($handler)(new CancelTransferCommand(
            movementId: self::MOVEMENT_ID,
            cancelledBy: self::CANCELLED_BY,
        ));

        self::assertSame(MovementStatus::CANCELLED, $movement->status());
        self::assertSame(self::CANCELLED_BY, $movement->cancelledByUserId());
        self::assertNotNull($movement->cancelledAt());
        self::assertTrue($serializedStock->isAvailable(), 'Serialized stock should be released after cancel');
    }

    public function testCancelsNonserializedTransferMergingWithAvailable(): void
    {
        $movement = $this->makeInTransitMovement(serializedStockId: null, quantity: 3);
        $reservedStock = $this->makeNonserializedStock(3);
        $reservedStock->reserve();

        $availableStock = NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject('01HZ0000000000000000000099'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            initialQuantity: 7,
        );

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);
        $movementRepo->expects($this->once())->method('save')->with($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocationAndStatus')
            ->willReturnCallback(fn ($productId, $locationId, $status) => match ($status) {
                NonserializedStockStatus::RESERVED => $reservedStock,
                NonserializedStockStatus::AVAILABLE => $availableStock,
                default => null,
            });
        $nonserializedRepo->expects($this->once())->method('remove')->with($reservedStock);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new CancelTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            transactionManager: $txManager,
        );

        ($handler)(new CancelTransferCommand(
            movementId: self::MOVEMENT_ID,
            cancelledBy: self::CANCELLED_BY,
        ));

        self::assertSame(MovementStatus::CANCELLED, $movement->status());
        self::assertSame(10, $availableStock->quantity(), 'Available stock should increase from 7 to 10');
    }

    public function testCancelsNonserializedTransferReleasingReserved(): void
    {
        $movement = $this->makeInTransitMovement(serializedStockId: null, quantity: 3);
        $reservedStock = $this->makeNonserializedStock(3);
        $reservedStock->reserve();

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);
        $movementRepo->expects($this->once())->method('save')->with($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocationAndStatus')
            ->willReturnCallback(fn ($productId, $locationId, $status) => match ($status) {
                NonserializedStockStatus::RESERVED => $reservedStock,
                default => null,
            });
        $nonserializedRepo->expects($this->never())->method('remove');
        $nonserializedRepo->expects($this->once())->method('save')->with($reservedStock);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new CancelTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            transactionManager: $txManager,
        );

        ($handler)(new CancelTransferCommand(
            movementId: self::MOVEMENT_ID,
            cancelledBy: self::CANCELLED_BY,
        ));

        self::assertSame(MovementStatus::CANCELLED, $movement->status());
        self::assertTrue($reservedStock->isAvailable(), 'Reserved stock should be released to available');
        self::assertSame(3, $reservedStock->quantity());
    }

    public function testThrowsWhenMovementNotFound(): void
    {
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn(null);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new CancelTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Movimiento no encontrado.');

        ($handler)(new CancelTransferCommand(
            movementId: self::MOVEMENT_ID,
            cancelledBy: self::CANCELLED_BY,
        ));
    }

    public function testThrowsWhenSerializedStockNotFound(): void
    {
        $movement = $this->makeInTransitMovement(
            serializedStockId: new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
        );

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn(null);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new CancelTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Stock serializado no encontrado.');

        ($handler)(new CancelTransferCommand(
            movementId: self::MOVEMENT_ID,
            cancelledBy: self::CANCELLED_BY,
        ));
    }

    public function testThrowsWhenReservedStockNotFound(): void
    {
        $movement = $this->makeInTransitMovement(serializedStockId: null, quantity: 3);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn(null);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new CancelTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('No se puede cancelar: el stock reservado no se encontró.');

        ($handler)(new CancelTransferCommand(
            movementId: self::MOVEMENT_ID,
            cancelledBy: self::CANCELLED_BY,
        ));
    }

    public function testThrowsWhenMovementHasNoSourceLocationAndNoSerialized(): void
    {
        $movement = Movement::create(
            id: new MovementIdValueObject(self::MOVEMENT_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            type: MovementType::TRANSFER,
            sourceLocationId: null,
            destinationLocationId: new LocationIdValueObject(self::DEST_LOCATION_ID),
            serializedStockId: null,
            quantity: 3,
        );
        $movement->startTransit();

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->expects($this->never())->method('findByProductAndLocationAndStatus');

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new CancelTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            transactionManager: $txManager,
        );

        ($handler)(new CancelTransferCommand(
            movementId: self::MOVEMENT_ID,
            cancelledBy: self::CANCELLED_BY,
        ));

        self::assertSame(MovementStatus::CANCELLED, $movement->status());
    }

    public function testRollsBackOnException(): void
    {
        $movement = $this->makeInTransitMovement(
            serializedStockId: new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
        );

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn(null);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new CancelTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);

        ($handler)(new CancelTransferCommand(
            movementId: self::MOVEMENT_ID,
            cancelledBy: self::CANCELLED_BY,
        ));
    }
}
