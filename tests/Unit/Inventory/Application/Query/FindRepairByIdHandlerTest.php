<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Application\Query\FindRepairById\FindRepairByIdHandler;
use App\Inventory\Application\Query\Response\RepairResponse;
use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Enums\RepairPriority;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindRepairByIdHandlerTest extends TestCase
{
    private const REPAIR_ID          = '01HZ0000000000000000000020';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000030';
    private const LABORATORY_ID      = '01HZ0000000000000000000002';
    private const ASSIGNED_BY        = '01HZ0000000000000000000040';
    private const TECHNICIAN_ID      = '01HZ0000000000000000000050';

    private function makeRepair(
        RepairStatus $status = RepairStatus::PENDING,
        ?string $technicianId = null,
    ): Repair {
        $repair = Repair::assign(
            new RepairIdValueObject(self::REPAIR_ID),
            new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
            new LocationIdValueObject(self::LABORATORY_ID),
            new UserIdValueObject(self::ASSIGNED_BY),
            'Reparación de display',
            RepairPriority::HIGH,
        );

        if ($technicianId !== null) {
            $repair->start(new UserIdValueObject($technicianId));
        }

        return $repair;
    }

    private function makeHandler(?Repair $repair = null): FindRepairByIdHandler
    {
        $repo = $this->createMock(RepairRepositoryInterface::class);
        $repo->method('findById')->willReturn($repair);

        return new FindRepairByIdHandler($repo);
    }

    public function testReturnsRepairResponseWhenFound(): void
    {
        $handler = $this->makeHandler($this->makeRepair());

        $response = ($handler)(self::REPAIR_ID);

        self::assertInstanceOf(RepairResponse::class, $response);
        self::assertSame(self::REPAIR_ID, $response->id);
        self::assertSame(self::SERIALIZED_STOCK_ID, $response->serializedStockId);
        self::assertSame(self::LABORATORY_ID, $response->laboratoryId);
        self::assertSame(self::ASSIGNED_BY, $response->assignedById);
        self::assertNull($response->technicianId);
        self::assertSame(RepairStatus::PENDING->value, $response->status);
        self::assertSame('Pendiente', $response->statusLabel);
        self::assertSame('Reparación de display', $response->notes);
        self::assertNotNull($response->createdAt);
        self::assertNull($response->startedAt);
        self::assertNull($response->completedAt);
    }

    public function testReturnsRepairWithTechnicianWhenInProgress(): void
    {
        $handler = $this->makeHandler(
            $this->makeRepair(RepairStatus::IN_PROGRESS, self::TECHNICIAN_ID),
        );

        $response = ($handler)(self::REPAIR_ID);

        self::assertSame(self::TECHNICIAN_ID, $response->technicianId);
        self::assertSame(RepairStatus::IN_PROGRESS->value, $response->status);
        self::assertSame('En proceso', $response->statusLabel);
        self::assertNotNull($response->startedAt);
    }

    public function testThrowsExceptionWhenNotFound(): void
    {
        $handler = $this->makeHandler(null);

        $this->expectException(RepairNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Reparación con ID "%s" no encontrada.', self::REPAIR_ID));

        ($handler)(self::REPAIR_ID);
    }
}
