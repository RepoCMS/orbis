<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\RejectTransfer\RejectTransferCommand;
use App\Inventory\Application\Command\RejectTransfer\RejectTransferHandler;
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

final class RejectTransferHandlerTest extends TestCase
{
    private const MOVEMENT_ID = '01HZ0000000000000000000010';
    private const PRODUCT_ID = '01HZ0000000000000000000003';
    private const SOURCE_LOCATION_ID = '01HZ0000000000000000000002';
    private const DEST_LOCATION_ID = '01HZ0000000000000000000001';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000004';
    private const NON_SERIALIZED_STOCK_ID = '01HZ0000000000000000000005';
    private const REJECTED_BY = '01HZ0000000000000000000070';
    private const REASON = 'Producto dañado';

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

    private function makeReservedStock(int $quantity = 3): NonserializedStock
    {
        $stock = NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject('01HZ0000000000000000000091'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            initialQuantity: $quantity,
        );
        $stock->reserve();

        return $stock;
    }

    // ─── Full reject serialized ──────────────────────────────────────────────

    public function testFullRejectSerializedTransfer(): void
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
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 1,
        ));

        self::assertSame(MovementStatus::REJECTED, $movement->status());
        self::assertSame(self::REJECTED_BY, $movement->rejectedByUserId());
        self::assertSame(self::REASON, $movement->rejectionReason());
        self::assertNotNull($movement->rejectedAt());
        self::assertTrue($serializedStock->isAvailable(), 'Serialized stock should be released after reject');
    }

    // ─── Full reject nonserialized ───────────────────────────────────────────

    public function testFullRejectNonserializedTransferMergingWithAvailable(): void
    {
        $movement = $this->makeInTransitMovement(serializedStockId: null, quantity: 3);
        $reservedStock = $this->makeReservedStock(3);
        $availableStock = NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject('01HZ0000000000000000000090'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            initialQuantity: 5,
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

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 3,
        ));

        self::assertSame(MovementStatus::REJECTED, $movement->status());
        self::assertSame(8, $availableStock->quantity(), 'Available stock should increase from 5 to 8');
    }

    public function testFullRejectNonserializedTransferReleasingReserved(): void
    {
        $movement = $this->makeInTransitMovement(serializedStockId: null, quantity: 3);
        $reservedStock = $this->makeReservedStock(3);

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

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 3,
        ));

        self::assertSame(MovementStatus::REJECTED, $movement->status());
        self::assertTrue($reservedStock->isAvailable(), 'Reserved stock should be released to available');
        self::assertSame(3, $reservedStock->quantity());
    }

    // ─── Partial reject nonserialized ────────────────────────────────────────

    public function testPartialRejectNonserializedWithAvailableStock(): void
    {
        $movement = $this->makeInTransitMovement(serializedStockId: null, quantity: 5);
        $reservedStock = $this->makeReservedStock(5);
        $availableStock = NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject('01HZ0000000000000000000090'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            initialQuantity: 10,
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

        $saveCount = 0;
        $nonserializedRepo->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function (NonserializedStock $s) use ($reservedStock, $availableStock, &$saveCount): void {
                $saveCount++;
                if ($saveCount === 1) {
                    self::assertSame($reservedStock, $s);
                } else {
                    self::assertSame($availableStock, $s);
                }
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->never())->method('generate');

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: 'Daño parcial',
            quantity: 2,
        ));

        self::assertStringContainsString('Rechazo parcial', $movement->notes() ?? '');
        self::assertSame(3, $reservedStock->quantity(), 'Reserved should be reduced from 5 to 3');
        self::assertSame(12, $availableStock->quantity(), 'Available should increase from 10 to 12');
    }

    public function testPartialRejectNonserializedCreatingNewStock(): void
    {
        $movement = $this->makeInTransitMovement(serializedStockId: null, quantity: 5);
        $reservedStock = $this->makeReservedStock(5);
        $newStockId = '01HZ0000000000000000000099';

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

        $savedNew = null;
        $saveCount = 0;
        $nonserializedRepo->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function (NonserializedStock $s) use (&$savedNew, &$saveCount): void {
                $saveCount++;
                if ($saveCount === 2) {
                    $savedNew = $s;
                }
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn($newStockId);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: 'Sobrante',
            quantity: 2,
        ));

        self::assertNotNull($savedNew);
        self::assertSame($newStockId, (string) $savedNew->id());
        self::assertSame(self::SOURCE_LOCATION_ID, (string) $savedNew->locationId());
        self::assertSame(2, $savedNew->quantity());
        self::assertTrue($savedNew->isAvailable());
        self::assertSame(3, $reservedStock->quantity(), 'Reserved should be reduced from 5 to 3');
    }

    // ─── Error cases ─────────────────────────────────────────────────────────

    public function testThrowsWhenMovementNotFound(): void
    {
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn(null);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Movimiento no encontrado.');

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 1,
        ));
    }

    public function testThrowsWhenMovementNotInTransit(): void
    {
        $movement = Movement::create(
            id: new MovementIdValueObject(self::MOVEMENT_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            type: MovementType::TRANSFER,
            sourceLocationId: new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            destinationLocationId: new LocationIdValueObject(self::DEST_LOCATION_ID),
            serializedStockId: null,
            quantity: 1,
        );

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Solo se puede rechazar una transferencia en tránsito.');

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 1,
        ));
    }

    public function testThrowsWhenQuantityZero(): void
    {
        $movement = $this->makeInTransitMovement(serializedStockId: null, quantity: 3);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('La cantidad a rechazar debe ser entre 1 y 3.');

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 0,
        ));
    }

    public function testThrowsWhenQuantityExceedsTotal(): void
    {
        $movement = $this->makeInTransitMovement(serializedStockId: null, quantity: 3);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('La cantidad a rechazar debe ser entre 1 y 3.');

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 5,
        ));
    }

    public function testThrowsWhenPartialRejectSerialized(): void
    {
        $movement = $this->makeInTransitMovement(
            serializedStockId: new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
        );

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->method('findById')->willReturn($movement);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->never())->method('begin');

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('La cantidad a rechazar debe ser entre 1 y 1.');

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 0,
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
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Stock serializado no encontrado.');

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 1,
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

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('No se puede rechazar: el stock reservado no se encontró.');

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 3,
        ));
    }

    public function testThrowsWhenMovementHasNoSourceLocation(): void
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
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('El movimiento no tiene ubicación de origen.');

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 3,
        ));
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
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new RejectTransferHandler(
            movementRepository: $movementRepo,
            serializedStockRepository: $serializedRepo,
            nonserializedStockRepository: $nonserializedRepo,
            idGenerator: $idGenerator,
            transactionManager: $txManager,
        );

        $this->expectException(InventoryDomainException::class);

        ($handler)(new RejectTransferCommand(
            movementId: self::MOVEMENT_ID,
            rejectedBy: self::REJECTED_BY,
            reason: self::REASON,
            quantity: 1,
        ));
    }
}
