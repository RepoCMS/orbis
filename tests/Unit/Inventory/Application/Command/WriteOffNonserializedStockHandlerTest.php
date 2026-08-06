<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\WriteOffNonserializedStock\WriteOffNonserializedStockCommand;
use App\Inventory\Application\Command\WriteOffNonserializedStock\WriteOffNonserializedStockHandler;
use App\Inventory\Application\Exception\StockNotFoundException;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use PHPUnit\Framework\TestCase;

final class WriteOffNonserializedStockHandlerTest extends TestCase
{
    private const STOCK_ID = '01HZ0000000000000000000001';
    private const PRODUCT_ID = '01HZ0000000000000000000002';
    private const LOCATION_ID = '01HZ0000000000000000000003';

    private function makeAvailableStock(int $quantity = 10): NonserializedStock
    {
        return NonserializedStock::initialize(
            id: new NonserializedStockIdValueObject(self::STOCK_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            locationId: new LocationIdValueObject(self::LOCATION_ID),
            initialQuantity: $quantity,
        );
    }

    public function testWritesOffAsDamaged(): void
    {
        $stock = $this->makeAvailableStock();

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findById')->willReturn($stock);
        $nonserializedRepo->expects($this->once())->method('save')->with($stock);

        $handler = new WriteOffNonserializedStockHandler(
            nonserializedStockRepository: $nonserializedRepo,
        );

        ($handler)(new WriteOffNonserializedStockCommand(
            nonserializedStockId: self::STOCK_ID,
            action: 'damage',
        ));

        self::assertTrue($stock->isDamaged());
    }

    public function testWritesOffAsDecommissioned(): void
    {
        $stock = $this->makeAvailableStock();

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findById')->willReturn($stock);
        $nonserializedRepo->expects($this->once())->method('save')->with($stock);

        $handler = new WriteOffNonserializedStockHandler(
            nonserializedStockRepository: $nonserializedRepo,
        );

        ($handler)(new WriteOffNonserializedStockCommand(
            nonserializedStockId: self::STOCK_ID,
            action: 'decommission',
        ));

        self::assertTrue($stock->isDecommissioned());
    }

    public function testThrowsWhenStockNotFound(): void
    {
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findById')->willReturn(null);

        $handler = new WriteOffNonserializedStockHandler(
            nonserializedStockRepository: $nonserializedRepo,
        );

        $this->expectException(StockNotFoundException::class);

        ($handler)(new WriteOffNonserializedStockCommand(
            nonserializedStockId: self::STOCK_ID,
            action: 'damage',
        ));
    }

    public function testThrowsWhenInvalidAction(): void
    {
        $stock = $this->makeAvailableStock();

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findById')->willReturn($stock);

        $handler = new WriteOffNonserializedStockHandler(
            nonserializedStockRepository: $nonserializedRepo,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Acción de baja no reconocida');

        ($handler)(new WriteOffNonserializedStockCommand(
            nonserializedStockId: self::STOCK_ID,
            action: 'invalid_action',
        ));
    }
}
