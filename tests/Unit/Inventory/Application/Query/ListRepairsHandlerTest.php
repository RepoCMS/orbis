<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Query\ListRepairs\ListRepairsHandler;
use App\Inventory\Application\Query\Response\RepairResponse;
use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Enums\RepairPriority;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListRepairsHandlerTest extends TestCase
{
    private const REPAIR_ID_1     = '01HZ0000000000000000000001';
    private const REPAIR_ID_2     = '01HZ0000000000000000000002';
    private const SERIALIZED_ID   = '01HZ0000000000000000000010';
    private const LAB_ID          = '01HZ0000000000000000000020';
    private const ASSIGNED_BY_ID  = '01HZ0000000000000000000030';

    private function makeRepair(string $id, RepairStatus $status): Repair
    {
        return new Repair(
            new RepairIdValueObject($id),
            new SerializedStockIdValueObject(self::SERIALIZED_ID),
            new LocationIdValueObject(self::LAB_ID),
            new UserIdValueObject(self::ASSIGNED_BY_ID),
            null,
            $status,
            RepairPriority::NORMAL,
            null,
            new \DateTimeImmutable('2025-01-15 10:00:00'),
            $status === RepairStatus::IN_PROGRESS ? new \DateTimeImmutable('2025-01-15 11:00:00') : null,
            $status === RepairStatus::COMPLETED || $status === RepairStatus::UNREPAIRABLE
                ? new \DateTimeImmutable('2025-01-15 12:00:00')
                : null,
        );
    }

    private function makeHandler(): RepairRepositoryInterface
    {
        return $this->createMock(RepairRepositoryInterface::class);
    }

    public function testReturnsPendingAndInProgressWhenNoStatusFilter(): void
    {
        $pending   = $this->makeRepair(self::REPAIR_ID_1, RepairStatus::PENDING);
        $inProgress = $this->makeRepair(self::REPAIR_ID_2, RepairStatus::IN_PROGRESS);

        $repo = $this->createMock(RepairRepositoryInterface::class);
        $repo->expects(self::exactly(2))
            ->method('findByStatus')
            ->willReturnMap([
                [RepairStatus::PENDING, [$pending]],
                [RepairStatus::IN_PROGRESS, [$inProgress]],
            ]);

        $handler = new ListRepairsHandler($repo);
        $result  = ($handler)();

        self::assertCount(2, $result);
        self::assertInstanceOf(RepairResponse::class, $result[0]);
        self::assertInstanceOf(RepairResponse::class, $result[1]);
    }

    public function testReturnsEmptyArrayWhenNoOpenRepairs(): void
    {
        $repo = $this->createMock(RepairRepositoryInterface::class);
        $repo->expects(self::exactly(2))
            ->method('findByStatus')
            ->willReturn([]);

        $handler = new ListRepairsHandler($repo);
        $result  = ($handler)();

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testFiltersByStatus(): void
    {
        $completed = $this->makeRepair(self::REPAIR_ID_1, RepairStatus::COMPLETED);

        $repo = $this->createMock(RepairRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findByStatus')
            ->with(RepairStatus::from('completed'))
            ->willReturn([$completed]);

        $handler = new ListRepairsHandler($repo);
        $result  = ($handler)('completed');

        self::assertCount(1, $result);
        self::assertSame('completed', $result[0]->status);
        self::assertSame('Completada', $result[0]->statusLabel);
    }

    public function testFiltersByUnrepairableStatus(): void
    {
        $unrepairable = $this->makeRepair(self::REPAIR_ID_1, RepairStatus::UNREPAIRABLE);

        $repo = $this->createMock(RepairRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findByStatus')
            ->with(RepairStatus::from('unrepairable'))
            ->willReturn([$unrepairable]);

        $handler = new ListRepairsHandler($repo);
        $result  = ($handler)('unrepairable');

        self::assertCount(1, $result);
        self::assertSame('unrepairable', $result[0]->status);
        self::assertSame('Irreparable', $result[0]->statusLabel);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $repair = $this->makeRepair(self::REPAIR_ID_1, RepairStatus::PENDING);

        $repo = $this->createMock(RepairRepositoryInterface::class);
        $repo->method('findByStatus')->willReturn([$repair]);

        $handler = new ListRepairsHandler($repo);
        $result  = ($handler)('pending');

        self::assertCount(1, $result);
        self::assertSame(self::REPAIR_ID_1, $result[0]->id);
        self::assertSame(self::SERIALIZED_ID, $result[0]->serializedStockId);
        self::assertSame(self::LAB_ID, $result[0]->laboratoryId);
        self::assertSame(self::ASSIGNED_BY_ID, $result[0]->assignedById);
        self::assertNull($result[0]->technicianId);
        self::assertNull($result[0]->notes);
        self::assertSame('pending', $result[0]->status);
        self::assertSame('Pendiente', $result[0]->statusLabel);
        self::assertNotNull($result[0]->createdAt);
        self::assertNull($result[0]->startedAt);
        self::assertNull($result[0]->completedAt);
    }
}
