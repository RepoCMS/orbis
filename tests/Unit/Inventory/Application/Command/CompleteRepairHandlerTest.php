<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\CompleteRepair\CompleteRepairCommand;
use App\Inventory\Application\Command\CompleteRepair\CompleteRepairHandler;
use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\Enums\SerializedStockStatus;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use PHPUnit\Framework\TestCase;

final class CompleteRepairHandlerTest extends TestCase
{
    private const REPAIR_ID      = '01HZ0000000000000000000001';
    private const STOCK_ID       = '01HZ0000000000000000000002';
    private const LAB_ID         = '01HZ0000000000000000000003';
    private const ASSIGNED_BY    = '01HZ0000000000000000000004';
    private const PRODUCT_ID     = '01HZ0000000000000000000005';

    public function testCompletesRepairSuccessfully(): void
    {
        $stock = SerializedStock::register(
            new SerializedStockIdValueObject(self::STOCK_ID),
            new ProductIdValueObject(self::PRODUCT_ID),
            new StockSerialNumberValueObject('SN-001'),
            new LocationIdValueObject(self::LAB_ID),
        );

        $repair = Repair::assign(
            id:                new RepairIdValueObject(self::REPAIR_ID),
            serializedStockId: new SerializedStockIdValueObject(self::STOCK_ID),
            laboratoryId:      new LocationIdValueObject(self::LAB_ID),
            assignedById:      new UserIdValueObject(self::ASSIGNED_BY),
            notes:             'Reparar lector de billetes',
        );

        $repair->start(new UserIdValueObject(self::ASSIGNED_BY));

        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $stockRepo  = $this->createMock(SerializedStockRepositoryInterface::class);

        $repairRepo->method('findById')->willReturn($repair);
        $stockRepo->method('findById')->willReturn($stock);

        $repairRepo->expects($this->once())->method('save')->with($repair);
        $stockRepo->expects($this->once())->method('save')->with($stock);

        $handler = new CompleteRepairHandler($repairRepo, $stockRepo);
        ($handler)(new CompleteRepairCommand(
            id:               self::REPAIR_ID,
            completionNotes:  'Reparación exitosa, lector funcional.',
        ));

        self::assertSame(RepairStatus::COMPLETED, $repair->status());
        self::assertNotNull($repair->completedAt());
        self::assertSame(SerializedStockStatus::AVAILABLE, $stock->status());
        self::assertSame(1, $stock->repairCount());
    }

    public function testThrowsWhenRepairNotFound(): void
    {
        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $stockRepo  = $this->createMock(SerializedStockRepositoryInterface::class);

        $repairRepo->method('findById')->willReturn(null);

        $handler = new CompleteRepairHandler($repairRepo, $stockRepo);

        $this->expectException(RepairNotFoundException::class);

        ($handler)(new CompleteRepairCommand(
            id:               self::REPAIR_ID,
            completionNotes:  null,
        ));
    }

    public function testHandlesMissingStockGracefully(): void
    {
        $repair = Repair::assign(
            id:                new RepairIdValueObject(self::REPAIR_ID),
            serializedStockId: new SerializedStockIdValueObject(self::STOCK_ID),
            laboratoryId:      new LocationIdValueObject(self::LAB_ID),
            assignedById:      new UserIdValueObject(self::ASSIGNED_BY),
            notes:             'Reparar lector de billetes',
        );

        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $stockRepo  = $this->createMock(SerializedStockRepositoryInterface::class);

        $repairRepo->method('findById')->willReturn($repair);
        $stockRepo->method('findById')->willReturn(null);

        $repairRepo->expects($this->once())->method('save')->with($repair);
        $stockRepo->expects($this->never())->method('save');

        $handler = new CompleteRepairHandler($repairRepo, $stockRepo);
        ($handler)(new CompleteRepairCommand(
            id:               self::REPAIR_ID,
            completionNotes:  'Sin stock asociado.',
        ));

        self::assertSame(RepairStatus::COMPLETED, $repair->status());
    }

    public function testCompletesRepairWithoutNotes(): void
    {
        $stock = SerializedStock::register(
            new SerializedStockIdValueObject(self::STOCK_ID),
            new ProductIdValueObject(self::PRODUCT_ID),
            new StockSerialNumberValueObject('SN-001'),
            new LocationIdValueObject(self::LAB_ID),
        );

        $repair = Repair::assign(
            id:                new RepairIdValueObject(self::REPAIR_ID),
            serializedStockId: new SerializedStockIdValueObject(self::STOCK_ID),
            laboratoryId:      new LocationIdValueObject(self::LAB_ID),
            assignedById:      new UserIdValueObject(self::ASSIGNED_BY),
        );

        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $stockRepo  = $this->createMock(SerializedStockRepositoryInterface::class);

        $repairRepo->method('findById')->willReturn($repair);
        $stockRepo->method('findById')->willReturn($stock);

        $repairRepo->expects($this->once())->method('save');
        $stockRepo->expects($this->once())->method('save');

        $handler = new CompleteRepairHandler($repairRepo, $stockRepo);
        ($handler)(new CompleteRepairCommand(
            id:               self::REPAIR_ID,
            completionNotes:  null,
        ));

        self::assertSame(RepairStatus::COMPLETED, $repair->status());
    }
}
