<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\RegisterConsumable\RegisterConsumableCommand;
use App\Inventory\Application\Command\RegisterConsumable\RegisterConsumableHandler;
use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Repositories\ConsumableRepositoryInterface;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RegisterConsumableHandlerTest extends TestCase
{
    private const REPAIR_ID = '01HZ0000000000000000000001';
    private const NEW_ID    = '01HZ0000000000000000000002';

    public function testRegistersConsumable(): void
    {
        $repair = Repair::assign(
            id: RepairIdValueObject::fromString(self::REPAIR_ID),
            serializedStockId: SerializedStockIdValueObject::fromString('01HZ0000000000000000000003'),
            laboratoryId: LocationIdValueObject::fromString('01HZ0000000000000000000004'),
            assignedById: new UserIdValueObject('01HZ0000000000000000000005'),
        );

        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $repairRepo->method('findById')->willReturn($repair);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::NEW_ID);

        $consumableRepo = $this->createMock(ConsumableRepositoryInterface::class);
        $consumableRepo->expects(self::once())->method('save');

        $handler = new RegisterConsumableHandler($repairRepo, $consumableRepo, $idGenerator);
        $result = ($handler)(new RegisterConsumableCommand(
            repairId: self::REPAIR_ID,
            productId: '01HZ0000000000000000000003',
            productCode: 'PRD-001',
            productName: 'Producto Test',
            quantity: 5,
            unitCost: 10.50,
            notes: 'Consumible de prueba',
        ));

        self::assertSame(self::NEW_ID, $result);
    }

    public function testThrowsWhenRepairNotFound(): void
    {
        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $repairRepo->method('findById')->willReturn(null);

        $handler = new RegisterConsumableHandler(
            $repairRepo,
            $this->createMock(ConsumableRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
        );

        $this->expectException(RepairNotFoundException::class);
        ($handler)(new RegisterConsumableCommand(
            repairId: '01HZ0000000000000000000099',
            productId: '01HZ0000000000000000000003',
            productCode: 'PRD-001',
            productName: 'Test',
            quantity: 1,
        ));
    }
}
