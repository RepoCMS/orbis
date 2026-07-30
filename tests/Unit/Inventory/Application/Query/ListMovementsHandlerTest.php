<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListMovements\ListMovementsHandler;
use App\Inventory\Application\Query\Response\MovementResponse;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListMovementsHandlerTest extends TestCase
{
    private const MOVEMENT_ID_1 = '01HZ0000000000000000000001';
    private const MOVEMENT_ID_2 = '01HZ0000000000000000000002';
    private const PRODUCT_ID    = '01HZ0000000000000000000010';
    private const LOCATION_A    = '01HZ0000000000000000000020';
    private const LOCATION_B    = '01HZ0000000000000000000021';

    private function makeMovement(
        string $id,
        MovementType $type,
        MovementStatus $status,
        int $quantity = 1,
    ): Movement {
        return new Movement(
            new MovementIdValueObject($id),
            new ProductIdValueObject(self::PRODUCT_ID),
            $type,
            new LocationIdValueObject(self::LOCATION_A),
            new LocationIdValueObject(self::LOCATION_B),
            null,
            $quantity,
            $status,
            null,
            new \DateTimeImmutable('2025-01-15 10:00:00'),
        );
    }

    private function makeHandler(array $movements): ListMovementsHandler
    {
        $repo = $this->createMock(MovementRepositoryInterface::class);
        $repo->method('findAll')->willReturn($movements);

        return new ListMovementsHandler($repo);
    }

    public function testReturnsAllMovements(): void
    {
        $m1 = $this->makeMovement(self::MOVEMENT_ID_1, MovementType::TRANSFER, MovementStatus::COMPLETED);
        $m2 = $this->makeMovement(self::MOVEMENT_ID_2, MovementType::ENTRY, MovementStatus::PENDING);

        $handler = $this->makeHandler([$m1, $m2]);
        $result  = ($handler)();

        self::assertCount(2, $result);
        self::assertInstanceOf(MovementResponse::class, $result[0]);
        self::assertInstanceOf(MovementResponse::class, $result[1]);
    }

    public function testReturnsEmptyArrayWhenNoMovements(): void
    {
        $handler = $this->makeHandler([]);
        $result  = ($handler)();

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $movement = $this->makeMovement(self::MOVEMENT_ID_1, MovementType::TRANSFER, MovementStatus::IN_TRANSIT, 3);

        $handler = $this->makeHandler([$movement]);
        $result  = ($handler)();

        self::assertCount(1, $result);
        self::assertSame(self::MOVEMENT_ID_1, $result[0]->id);
        self::assertSame(self::PRODUCT_ID, $result[0]->productId);
        self::assertSame('transfer', $result[0]->type);
        self::assertSame('Traslado', $result[0]->typeLabel);
        self::assertSame(self::LOCATION_A, $result[0]->sourceLocationId);
        self::assertSame(self::LOCATION_B, $result[0]->destinationLocationId);
        self::assertSame(3, $result[0]->quantity);
        self::assertSame('in_transit', $result[0]->status);
        self::assertSame('En tránsito', $result[0]->statusLabel);
        self::assertNull($result[0]->serializedStockId);
        self::assertNull($result[0]->completedAt);
    }

    public function testMapsAllMovementTypes(): void
    {
        $entry    = $this->makeMovement(self::MOVEMENT_ID_1, MovementType::ENTRY, MovementStatus::COMPLETED);
        $transfer = $this->makeMovement(self::MOVEMENT_ID_2, MovementType::TRANSFER, MovementStatus::COMPLETED);

        $entryMovement = new Movement(
            new MovementIdValueObject('01HZ0000000000000000000003'),
            new ProductIdValueObject(self::PRODUCT_ID),
            MovementType::EXIT,
            new LocationIdValueObject(self::LOCATION_A),
            null,
            null,
            1,
            MovementStatus::COMPLETED,
            null,
            new \DateTimeImmutable('2025-01-15 10:00:00'),
        );

        $handler = $this->makeHandler([$entry, $transfer, $entryMovement]);
        $result  = ($handler)();

        self::assertCount(3, $result);
        self::assertSame('entry', $result[0]->type);
        self::assertSame('Entrada', $result[0]->typeLabel);
        self::assertSame('transfer', $result[1]->type);
        self::assertSame('Traslado', $result[1]->typeLabel);
        self::assertSame('exit', $result[2]->type);
        self::assertSame('Salida', $result[2]->typeLabel);
    }

    public function testMapsAllMovementStatuses(): void
    {
        $pending   = $this->makeMovement(self::MOVEMENT_ID_1, MovementType::TRANSFER, MovementStatus::PENDING);
        $inTransit = $this->makeMovement(self::MOVEMENT_ID_2, MovementType::TRANSFER, MovementStatus::IN_TRANSIT);
        $completed = $this->makeMovement(
            '01HZ0000000000000000000003',
            MovementType::TRANSFER,
            MovementStatus::COMPLETED,
        );
        $cancelled = $this->makeMovement(
            '01HZ0000000000000000000004',
            MovementType::TRANSFER,
            MovementStatus::CANCELLED,
        );

        $handler = $this->makeHandler([$pending, $inTransit, $completed, $cancelled]);
        $result  = ($handler)();

        self::assertCount(4, $result);
        self::assertSame('pending', $result[0]->status);
        self::assertSame('Pendiente', $result[0]->statusLabel);
        self::assertSame('in_transit', $result[1]->status);
        self::assertSame('En tránsito', $result[1]->statusLabel);
        self::assertSame('completed', $result[2]->status);
        self::assertSame('Completado', $result[2]->statusLabel);
        self::assertSame('cancelled', $result[3]->status);
        self::assertSame('Cancelado', $result[3]->statusLabel);
    }
}
