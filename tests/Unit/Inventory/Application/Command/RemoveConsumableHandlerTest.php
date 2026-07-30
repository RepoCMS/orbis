<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\RemoveConsumable\RemoveConsumableCommand;
use App\Inventory\Application\Command\RemoveConsumable\RemoveConsumableHandler;
use App\Inventory\Application\Exception\ConsumableNotFoundException;
use App\Inventory\Domain\Entities\Consumable;
use App\Inventory\Domain\Repositories\ConsumableRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ConsumableIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use PHPUnit\Framework\TestCase;

final class RemoveConsumableHandlerTest extends TestCase
{
    private const CONSUMABLE_ID = '01HZ0000000000000000000001';
    private const REPAIR_ID     = '01HZ0000000000000000000002';

    public function testRemovesConsumable(): void
    {
        $consumable = Consumable::register(
            id: ConsumableIdValueObject::fromString(self::CONSUMABLE_ID),
            repairId: RepairIdValueObject::fromString(self::REPAIR_ID),
            productId: '01HZ0000000000000000000003',
            productCode: 'PRD-001',
            productName: 'Producto Test',
            quantity: 5,
        );

        $repo = $this->createMock(ConsumableRepositoryInterface::class);
        $repo->method('findById')->willReturn($consumable);
        $repo->expects(self::once())->method('delete')->with($consumable);

        $handler = new RemoveConsumableHandler($repo);
        ($handler)(new RemoveConsumableCommand(
            consumableId: self::CONSUMABLE_ID,
            repairId: self::REPAIR_ID,
        ));
    }

    public function testThrowsWhenConsumableNotFound(): void
    {
        $repo = $this->createMock(ConsumableRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new RemoveConsumableHandler($repo);

        $this->expectException(ConsumableNotFoundException::class);
        ($handler)(new RemoveConsumableCommand(
            consumableId: '01HZ0000000000000000000099',
            repairId: self::REPAIR_ID,
        ));
    }
}
