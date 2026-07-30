<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\FindMovementById\FindMovementByIdHandler;
use App\Inventory\Application\Query\Response\MovementResponse;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindMovementByIdHandlerTest extends TestCase
{
    private const MOVEMENT_ID   = '01HZ0000000000000000000010';
    private const PRODUCT_ID    = '01HZ0000000000000000000001';
    private const SOURCE_LOC    = '01HZ0000000000000000000002';
    private const DEST_LOC      = '01HZ0000000000000000000003';

    private function makeMovement(
        MovementStatus $status = MovementStatus::PENDING,
        ?string $completedAt = null,
    ): Movement {
        $movement = Movement::create(
            new MovementIdValueObject(self::MOVEMENT_ID),
            new ProductIdValueObject(self::PRODUCT_ID),
            MovementType::TRANSFER,
            new LocationIdValueObject(self::SOURCE_LOC),
            new LocationIdValueObject(self::DEST_LOC),
            null,
            5,
            'Traslado de prueba',
        );

        if ($completedAt !== null) {
            $movement->complete();
        }

        return $movement;
    }

    private function makeHandler(?Movement $movement = null): FindMovementByIdHandler
    {
        $repo = $this->createMock(MovementRepositoryInterface::class);
        $repo->method('findById')->willReturn($movement);

        return new FindMovementByIdHandler($repo);
    }

    public function testReturnsMovementResponseWhenFound(): void
    {
        $handler = $this->makeHandler($this->makeMovement());

        $response = ($handler)(self::MOVEMENT_ID);

        self::assertInstanceOf(MovementResponse::class, $response);
        self::assertSame(self::MOVEMENT_ID, $response->id);
        self::assertSame(self::PRODUCT_ID, $response->productId);
        self::assertSame(MovementType::TRANSFER->value, $response->type);
        self::assertSame('Traslado', $response->typeLabel);
        self::assertSame(self::SOURCE_LOC, $response->sourceLocationId);
        self::assertSame(self::DEST_LOC, $response->destinationLocationId);
        self::assertNull($response->serializedStockId);
        self::assertSame(5, $response->quantity);
        self::assertSame(MovementStatus::PENDING->value, $response->status);
        self::assertSame('Pendiente', $response->statusLabel);
        self::assertSame('Traslado de prueba', $response->notes);
        self::assertNotNull($response->createdAt);
        self::assertNull($response->completedAt);
    }

    public function testReturnsCompletedMovementWithDate(): void
    {
        $handler = $this->makeHandler($this->makeMovement(status: MovementStatus::COMPLETED, completedAt: 'now'));

        $response = ($handler)(self::MOVEMENT_ID);

        self::assertSame(MovementStatus::COMPLETED->value, $response->status);
        self::assertSame('Completado', $response->statusLabel);
        self::assertNotNull($response->completedAt);
    }

    public function testThrowsRuntimeExceptionWhenNotFound(): void
    {
        $handler = $this->makeHandler(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Movimiento con ID "%s" no encontrado.', self::MOVEMENT_ID));

        ($handler)(self::MOVEMENT_ID);
    }
}
