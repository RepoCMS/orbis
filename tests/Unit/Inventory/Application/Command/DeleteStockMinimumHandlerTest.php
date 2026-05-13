<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\DeleteStockMinimum\DeleteStockMinimumCommand;
use App\Inventory\Application\Command\DeleteStockMinimum\DeleteStockMinimumHandler;
use App\Inventory\Domain\Entities\StockMinimum;
use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;
use PHPUnit\Framework\TestCase;

final class DeleteStockMinimumHandlerTest extends TestCase
{
    private const MINIMUM_ID  = '01HZ0000000000000000000010';
    private const PRODUCT_ID  = '01HZ0000000000000000000002';
    private const LOCATION_ID = '01HZ0000000000000000000003';

    private function makeMinimum(): StockMinimum
    {
        return StockMinimum::define(
            id:             new StockMinimumIdValueObject(self::MINIMUM_ID),
            productId:      new ProductIdValueObject(self::PRODUCT_ID),
            locationId:     new LocationIdValueObject(self::LOCATION_ID),
            idealQuantity:  10,
            alertThreshold: 3,
        );
    }

    // ─── Happy path ───────────────────────────────────────────────────────────

    public function testDeleteCallsRepository(): void
    {
        $repo = $this->createMock(StockMinimumRepositoryInterface::class);
        $repo->method('findById')->willReturn($this->makeMinimum());

        $repo->expects($this->once())
            ->method('delete')
            ->with($this->callback(
                fn (StockMinimumIdValueObject $id) => (string) $id === self::MINIMUM_ID
            ));

        $handler = new DeleteStockMinimumHandler($repo);
        ($handler)(new DeleteStockMinimumCommand(self::MINIMUM_ID));
    }

    // ─── Not found ────────────────────────────────────────────────────────────

    public function testThrowsWhenMinimumNotFound(): void
    {
        $repo = $this->createMock(StockMinimumRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $repo->expects($this->never())->method('delete');

        $this->expectException(\LogicException::class);

        $handler = new DeleteStockMinimumHandler($repo);
        ($handler)(new DeleteStockMinimumCommand(self::MINIMUM_ID));
    }
}
