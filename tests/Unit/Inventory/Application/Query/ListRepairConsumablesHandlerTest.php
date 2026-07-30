<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListRepairConsumables\ListRepairConsumablesHandler;
use App\Inventory\Application\Query\ListRepairConsumables\ListRepairConsumablesQuery;
use App\Inventory\Application\Query\Response\ConsumableResponse;
use App\Inventory\Domain\Entities\Consumable;
use App\Inventory\Domain\Repositories\ConsumableRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ConsumableIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListRepairConsumablesHandlerTest extends TestCase
{
    private const CONSUMABLE_ID_1 = '01HZ0000000000000000000001';
    private const CONSUMABLE_ID_2 = '01HZ0000000000000000000002';
    private const REPAIR_ID       = '01HZ0000000000000000000010';

    private function makeConsumable(string $id, int $quantity = 1, ?float $unitCost = null, ?string $notes = null): Consumable
    {
        return Consumable::register(
            new ConsumableIdValueObject($id),
            new RepairIdValueObject(self::REPAIR_ID),
            'PROD-001',
            'ATM-001',
            'Cable HDMI',
            $quantity,
            $unitCost,
            $notes,
        );
    }

    private function makeHandler(array $consumables): ListRepairConsumablesHandler
    {
        $repo = $this->createMock(ConsumableRepositoryInterface::class);
        $repo->method('findByRepairId')->willReturn($consumables);

        return new ListRepairConsumablesHandler($repo);
    }

    public function testReturnsAllConsumablesForRepair(): void
    {
        $c1 = $this->makeConsumable(self::CONSUMABLE_ID_1, 2, 5.50, 'Cable HDMI 2m');
        $c2 = $this->makeConsumable(self::CONSUMABLE_ID_2, 1);

        $handler = $this->makeHandler([$c1, $c2]);
        $query   = new ListRepairConsumablesQuery(self::REPAIR_ID);
        $result  = ($handler)($query);

        self::assertCount(2, $result);
        self::assertInstanceOf(ConsumableResponse::class, $result[0]);
        self::assertInstanceOf(ConsumableResponse::class, $result[1]);
    }

    public function testReturnsEmptyArrayWhenNoConsumables(): void
    {
        $handler = $this->makeHandler([]);
        $query   = new ListRepairConsumablesQuery(self::REPAIR_ID);
        $result  = ($handler)($query);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $consumable = $this->makeConsumable(self::CONSUMABLE_ID_1, 3, 10.00, 'Pasta térmica');

        $handler = $this->makeHandler([$consumable]);
        $query   = new ListRepairConsumablesQuery(self::REPAIR_ID);
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertSame(self::CONSUMABLE_ID_1, $result[0]->id);
        self::assertSame(self::REPAIR_ID, $result[0]->repairId);
        self::assertSame('PROD-001', $result[0]->productId);
        self::assertSame('ATM-001', $result[0]->productCode);
        self::assertSame('Cable HDMI', $result[0]->productName);
        self::assertSame(3, $result[0]->quantity);
        self::assertSame(10.00, $result[0]->unitCost);
        self::assertSame('Pasta térmica', $result[0]->notes);
        self::assertNotNull($result[0]->createdAt);
    }

    public function testResponseWithNullUnitCostAndNotes(): void
    {
        $consumable = $this->makeConsumable(self::CONSUMABLE_ID_1);

        $handler = $this->makeHandler([$consumable]);
        $query   = new ListRepairConsumablesQuery(self::REPAIR_ID);
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertNull($result[0]->unitCost);
        self::assertNull($result[0]->notes);
    }

    public function testDelegatesToRepositoryWithCorrectRepairId(): void
    {
        $repo = $this->createMock(ConsumableRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findByRepairId')
            ->with(self::callback(fn(RepairIdValueObject $v) => (string) $v === self::REPAIR_ID))
            ->willReturn([]);

        $handler = new ListRepairConsumablesHandler($repo);
        $query   = new ListRepairConsumablesQuery(self::REPAIR_ID);
        ($handler)($query);
    }
}
