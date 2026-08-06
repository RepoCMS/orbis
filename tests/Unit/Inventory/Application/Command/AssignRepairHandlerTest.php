<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\AssignRepair\AssignRepairCommand;
use App\Inventory\Application\Command\AssignRepair\AssignRepairHandler;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\SerializedStockStatus;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use PHPUnit\Framework\TestCase;

final class AssignRepairHandlerTest extends TestCase
{
    private const STOCK_ID     = '01HZ0000000000000000000001';
    private const LAB_ID       = '01HZ0000000000000000000002';
    private const ASSIGNED_BY  = '01HZ0000000000000000000003';
    private const REPAIR_ID    = '01HZ0000000000000000000004';
    private const PRODUCT_ID   = '01HZ0000000000000000000005';

    public function testAssignsRepairSuccessfully(): void
    {
        $stock = SerializedStock::register(
            new SerializedStockIdValueObject(self::STOCK_ID),
            new ProductIdValueObject(self::PRODUCT_ID),
            new StockSerialNumberValueObject('SN-001'),
            new LocationIdValueObject(self::LAB_ID),
        );

        $lab = Location::create(
            id: new LocationIdValueObject(self::LAB_ID),
            name: new LocationNameValueObject('Laboratorio Central'),
            city: 'Quito',
            address: '',
            type: LocationType::LABORATORY,
        );

        $serialRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $locRepo    = $this->createMock(LocationRepositoryInterface::class);
        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $idGen      = $this->createMock(IdGeneratorInterface::class);

        $serialRepo->method('findById')->willReturn($stock);
        $locRepo->method('findById')->willReturn($lab);
        $idGen->method('generate')->willReturn(self::REPAIR_ID);

        $serialRepo->expects($this->once())->method('save')->with($stock);
        $repairRepo->expects($this->once())->method('save')->with($this->isInstanceOf(Repair::class));

        $handler = new AssignRepairHandler($repairRepo, $serialRepo, $locRepo, $idGen);
        ($handler)(new AssignRepairCommand(
            serializedStockId: self::STOCK_ID,
            laboratoryId:      self::LAB_ID,
            assignedById:      self::ASSIGNED_BY,
            notes:             'Reparar lector de billetes',
        ));

        self::assertSame(SerializedStockStatus::DAMAGED, $stock->status());
    }

    public function testThrowsWhenStockNotFound(): void
    {
        $serialRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $locRepo    = $this->createMock(LocationRepositoryInterface::class);
        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $idGen      = $this->createMock(IdGeneratorInterface::class);

        $serialRepo->method('findById')->willReturn(null);

        $handler = new AssignRepairHandler($repairRepo, $serialRepo, $locRepo, $idGen);

        $this->expectException(ApplicationException::class);

        ($handler)(new AssignRepairCommand(
            serializedStockId: self::STOCK_ID,
            laboratoryId:      self::LAB_ID,
            assignedById:      self::ASSIGNED_BY,
            notes:             null,
        ));
    }

    public function testThrowsWhenLaboratoryNotFound(): void
    {
        $stock = SerializedStock::register(
            new SerializedStockIdValueObject(self::STOCK_ID),
            new ProductIdValueObject(self::PRODUCT_ID),
            new StockSerialNumberValueObject('SN-001'),
            new LocationIdValueObject(self::LAB_ID),
        );

        $serialRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $locRepo    = $this->createMock(LocationRepositoryInterface::class);
        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $idGen      = $this->createMock(IdGeneratorInterface::class);

        $serialRepo->method('findById')->willReturn($stock);
        $locRepo->method('findById')->willReturn(null);

        $handler = new AssignRepairHandler($repairRepo, $serialRepo, $locRepo, $idGen);

        $this->expectException(ApplicationException::class);

        ($handler)(new AssignRepairCommand(
            serializedStockId: self::STOCK_ID,
            laboratoryId:      self::LAB_ID,
            assignedById:      self::ASSIGNED_BY,
            notes:             null,
        ));
    }

    public function testThrowsWhenLocationIsNotLaboratory(): void
    {
        $stock = SerializedStock::register(
            new SerializedStockIdValueObject(self::STOCK_ID),
            new ProductIdValueObject(self::PRODUCT_ID),
            new StockSerialNumberValueObject('SN-001'),
            new LocationIdValueObject(self::LAB_ID),
        );

        $warehouse = Location::create(
            id: new LocationIdValueObject(self::LAB_ID),
            name: new LocationNameValueObject('Bodega'),
            city: 'Quito',
            address: '',
            type: LocationType::WAREHOUSE,
        );

        $serialRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $locRepo    = $this->createMock(LocationRepositoryInterface::class);
        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $idGen      = $this->createMock(IdGeneratorInterface::class);

        $serialRepo->method('findById')->willReturn($stock);
        $locRepo->method('findById')->willReturn($warehouse);

        $handler = new AssignRepairHandler($repairRepo, $serialRepo, $locRepo, $idGen);

        $this->expectException(ApplicationException::class);

        ($handler)(new AssignRepairCommand(
            serializedStockId: self::STOCK_ID,
            laboratoryId:      self::LAB_ID,
            assignedById:      self::ASSIGNED_BY,
            notes:             null,
        ));
    }
}
