<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\StartRepair\StartRepairCommand;
use App\Inventory\Application\Command\StartRepair\StartRepairHandler;
use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use PHPUnit\Framework\TestCase;

final class StartRepairHandlerTest extends TestCase
{
    private const REPAIR_ID      = '01HZ0000000000000000000001';
    private const STOCK_ID       = '01HZ0000000000000000000002';
    private const LAB_ID         = '01HZ0000000000000000000003';
    private const ASSIGNED_BY    = '01HZ0000000000000000000004';
    private const TECHNICIAN_ID  = '01HZ0000000000000000000005';

    public function testStartsRepairSuccessfully(): void
    {
        $repair = Repair::assign(
            id:                new RepairIdValueObject(self::REPAIR_ID),
            serializedStockId: new SerializedStockIdValueObject(self::STOCK_ID),
            laboratoryId:      new LocationIdValueObject(self::LAB_ID),
            assignedById:      new UserIdValueObject(self::ASSIGNED_BY),
            notes:             'Reparar lector de billetes',
        );

        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $repairRepo->method('findById')->willReturn($repair);
        $repairRepo->expects($this->once())->method('save')->with($repair);

        $handler = new StartRepairHandler($repairRepo);
        ($handler)(new StartRepairCommand(
            id:            self::REPAIR_ID,
            technicianId:  self::TECHNICIAN_ID,
        ));

        self::assertSame(RepairStatus::IN_PROGRESS, $repair->status());
        self::assertNotNull($repair->startedAt());
    }

    public function testThrowsWhenRepairNotFound(): void
    {
        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $repairRepo->method('findById')->willReturn(null);

        $handler = new StartRepairHandler($repairRepo);

        $this->expectException(RepairNotFoundException::class);

        ($handler)(new StartRepairCommand(
            id:            self::REPAIR_ID,
            technicianId:  self::TECHNICIAN_ID,
        ));
    }

    public function testThrowsWhenRepairNotInPendingState(): void
    {
        $repair = Repair::assign(
            id:                new RepairIdValueObject(self::REPAIR_ID),
            serializedStockId: new SerializedStockIdValueObject(self::STOCK_ID),
            laboratoryId:      new LocationIdValueObject(self::LAB_ID),
            assignedById:      new UserIdValueObject(self::ASSIGNED_BY),
            notes:             'Reparar lector de billetes',
        );

        $repair->complete(null);

        self::assertSame(RepairStatus::COMPLETED, $repair->status());

        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $repairRepo->method('findById')->willReturn($repair);
        $repairRepo->expects($this->once())->method('save')->with($repair);

        $handler = new StartRepairHandler($repairRepo);

        ($handler)(new StartRepairCommand(
            id:            self::REPAIR_ID,
            technicianId:  self::TECHNICIAN_ID,
        ));

        self::assertSame(RepairStatus::IN_PROGRESS, $repair->status());
    }
}
