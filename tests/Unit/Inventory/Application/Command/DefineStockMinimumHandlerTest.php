<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\DefineStockMinimum\DefineStockMinimumCommand;
use App\Inventory\Application\Command\DefineStockMinimum\DefineStockMinimumHandler;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Entities\StockMinimum;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class DefineStockMinimumHandlerTest extends TestCase
{
    private const PRODUCT_ID  = '01HZ0000000000000000000002';
    private const LOCATION_ID = '01HZ0000000000000000000003';
    private const NEW_ID      = '01HZ0000000000000000000099';

    private function makeProduct(): Product
    {
        return Product::add(
            new ProductIdValueObject(self::PRODUCT_ID),
            new ProductCodeAssignedBySupplierValueObject('CODE-001'),
            new ProductNameValueObject('ATM Display'),
            'Description',
            ProductTrackingType::SERIALIZED,
        );
    }

    private function makeLocation(): Location
    {
        return Location::create(
            new LocationIdValueObject(self::LOCATION_ID),
            new LocationNameValueObject('Almacén Central'),
            'Guayaquil',
            'Av. Principal 123',
            LocationType::WAREHOUSE,
        );
    }

    private function makeExistingMinimum(): StockMinimum
    {
        return StockMinimum::define(
            id:             new StockMinimumIdValueObject('01HZ0000000000000000000010'),
            productId:      new ProductIdValueObject(self::PRODUCT_ID),
            locationId:     new LocationIdValueObject(self::LOCATION_ID),
            idealQuantity:  10,
            alertThreshold: 3,
        );
    }

    private function makeHandler(
        StockMinimumRepositoryInterface $repo,
        ?Product                        $product      = null,
        ?Location                       $location     = null,
        string                          $generatedId  = self::NEW_ID,
    ): DefineStockMinimumHandler {
        $productRepo  = $this->createMock(ProductRepositoryInterface::class);
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);

        $productRepo->method('findById')->willReturn($product ?? $this->makeProduct());
        $locationRepo->method('findById')->willReturn($location ?? $this->makeLocation());
        $idGenerator->method('generate')->willReturn($generatedId);

        return new DefineStockMinimumHandler($repo, $productRepo, $locationRepo, $idGenerator);
    }

    // ─── Create (no existing) ─────────────────────────────────────────────────

    public function testCreatesNewMinimumWhenNoneExists(): void
    {
        $repo = $this->createMock(StockMinimumRepositoryInterface::class);
        $repo->method('findByProductAndLocation')->willReturn(null);

        $saved = null;
        $repo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (StockMinimum $m) use (&$saved): void {
                $saved = $m;
            });

        $handler = $this->makeHandler($repo);
        ($handler)(new DefineStockMinimumCommand(self::PRODUCT_ID, self::LOCATION_ID, 10, 3));

        self::assertNotNull($saved);
        self::assertSame(10, $saved->idealQuantity());
        self::assertSame(3, $saved->alertThreshold());
        self::assertSame(self::NEW_ID, (string) $saved->id());
    }

    // ─── Update (existing) ────────────────────────────────────────────────────

    public function testUpdatesExistingMinimum(): void
    {
        $existing = $this->makeExistingMinimum();

        $repo = $this->createMock(StockMinimumRepositoryInterface::class);
        $repo->method('findByProductAndLocation')->willReturn($existing);

        $saved = null;
        $repo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (StockMinimum $m) use (&$saved): void {
                $saved = $m;
            });

        $handler = $this->makeHandler($repo);
        ($handler)(new DefineStockMinimumCommand(self::PRODUCT_ID, self::LOCATION_ID, 20, 7));

        self::assertSame(20, $saved->idealQuantity());
        self::assertSame(7, $saved->alertThreshold());
        // same id preserved
        self::assertSame('01HZ0000000000000000000010', (string) $saved->id());
    }

    // ─── Guard: product not found ─────────────────────────────────────────────

    public function testThrowsWhenProductDoesNotExist(): void
    {
        $repo = $this->createMock(StockMinimumRepositoryInterface::class);

        $handler = $this->makeHandler($repo, product: null);

        // Override product repo to return null
        $productRepo  = $this->createMock(ProductRepositoryInterface::class);
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);

        $productRepo->method('findById')->willReturn(null);
        $locationRepo->method('findById')->willReturn($this->makeLocation());

        $handler = new DefineStockMinimumHandler($repo, $productRepo, $locationRepo, $idGenerator);

        $this->expectException(\LogicException::class);

        ($handler)(new DefineStockMinimumCommand(self::PRODUCT_ID, self::LOCATION_ID, 10, 3));
    }

    // ─── Guard: location not found ────────────────────────────────────────────

    public function testThrowsWhenLocationDoesNotExist(): void
    {
        $repo         = $this->createMock(StockMinimumRepositoryInterface::class);
        $productRepo  = $this->createMock(ProductRepositoryInterface::class);
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $idGenerator  = $this->createMock(IdGeneratorInterface::class);

        $productRepo->method('findById')->willReturn($this->makeProduct());
        $locationRepo->method('findById')->willReturn(null);

        $handler = new DefineStockMinimumHandler($repo, $productRepo, $locationRepo, $idGenerator);

        $this->expectException(\LogicException::class);

        ($handler)(new DefineStockMinimumCommand(self::PRODUCT_ID, self::LOCATION_ID, 10, 3));
    }
}
