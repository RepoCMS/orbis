<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\GeneratePurchaseSuggestions\GeneratePurchaseSuggestionsHandler;
use App\Inventory\Application\Query\GeneratePurchaseSuggestions\GeneratePurchaseSuggestionsQuery;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Entities\StockMinimum;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;
use PHPUnit\Framework\TestCase;

final class GeneratePurchaseSuggestionsHandlerTest extends TestCase
{
    private const PRODUCT_ID       = '01HZ0000000000000000000010';
    private const LOCATION_ID      = '01HZ0000000000000000000020';
    private const LOCATION_ID_2    = '01HZ0000000000000000000021';
    private const STOCK_MIN_ID     = '01HZ0000000000000000000030';
    private const STOCK_MIN_ID_2   = '01HZ0000000000000000000031';
    private const PRODUCT_CODE     = 'PROD-001';
    private const PRODUCT_NAME     = 'Producto de prueba';
    private const LOCATION_NAME    = 'Bodega Central';

    private function makeProduct(bool $serialized = true): Product
    {
        return Product::add(
            id: new ProductIdValueObject(self::PRODUCT_ID),
            productCode: new ProductCodeAssignedBySupplierValueObject(self::PRODUCT_CODE),
            name: new ProductNameValueObject(self::PRODUCT_NAME),
            description: 'Descripci' . "\xC3\xB3" . 'n de prueba',
            trackingType: $serialized ? ProductTrackingType::SERIALIZED : ProductTrackingType::NON_SERIALIZED,
        );
    }

    private function makeLocation(string $id = self::LOCATION_ID): Location
    {
        return Location::create(
            id: new LocationIdValueObject($id),
            name: new LocationNameValueObject(self::LOCATION_NAME),
            city: 'Ciudad',
            address: 'Direcci' . "\xC3\xB3" . 'n',
            type: LocationType::WAREHOUSE,
        );
    }

    private function makeStockMinimum(
        string $id = self::STOCK_MIN_ID,
        string $productId = self::PRODUCT_ID,
        string $locationId = self::LOCATION_ID,
        int $idealQuantity = 10,
        int $alertThreshold = 3,
    ): StockMinimum {
        return StockMinimum::define(
            id: new StockMinimumIdValueObject($id),
            productId: new ProductIdValueObject($productId),
            locationId: new LocationIdValueObject($locationId),
            idealQuantity: $idealQuantity,
            alertThreshold: $alertThreshold,
        );
    }

    private function makePoItemRepoMock(): PurchaseOrderItemRepositoryInterface
    {
        return $this->getMockBuilder(PurchaseOrderItemRepositoryInterface::class)
            ->onlyMethods(['findById', 'findByPurchaseOrder', 'save', 'findLastPurchaseDateByProduct'])
            ->getMock();
    }

    public function testReturnsSuggestionWhenStockBelowIdeal(): void
    {
        $minimum   = $this->makeStockMinimum();
        $product   = $this->makeProduct();
        $location  = $this->makeLocation();

        $stockMinRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $stockMinRepo->method('findAll')->willReturn([$minimum]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('countAvailableByProductAndLocation')->willReturn(3);

        $nonSerializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $poItemRepo = $this->makePoItemRepoMock();
        $poItemRepo->method('findLastPurchaseDateByProduct')->willReturn(null);

        $handler = new GeneratePurchaseSuggestionsHandler(
            $stockMinRepo,
            $productRepo,
            $locationRepo,
            $serializedRepo,
            $nonSerializedRepo,
            $poItemRepo,
        );

        $result = ($handler)(new GeneratePurchaseSuggestionsQuery());

        self::assertCount(1, $result);
        self::assertSame(self::PRODUCT_CODE, $result[0]->productCode);
        self::assertSame(self::PRODUCT_NAME, $result[0]->productName);
        self::assertSame(3, $result[0]->currentStock);
        self::assertSame(10, $result[0]->idealQuantity);
        self::assertSame(7, $result[0]->suggestedQuantity);
        self::assertSame(7, $result[0]->gap());
        self::assertNull($result[0]->lastPurchaseDate);
        self::assertNull($result[0]->frequentSupplier);
    }

    public function testReturnsEmptyWhenStockEqualsIdeal(): void
    {
        $minimum  = $this->makeStockMinimum(idealQuantity: 5);
        $product  = $this->makeProduct();
        $location = $this->makeLocation();

        $stockMinRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $stockMinRepo->method('findAll')->willReturn([$minimum]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('countAvailableByProductAndLocation')->willReturn(5);

        $nonSerializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $poItemRepo = $this->makePoItemRepoMock();

        $handler = new GeneratePurchaseSuggestionsHandler(
            $stockMinRepo,
            $productRepo,
            $locationRepo,
            $serializedRepo,
            $nonSerializedRepo,
            $poItemRepo,
        );

        $result = ($handler)(new GeneratePurchaseSuggestionsQuery());

        self::assertCount(0, $result);
    }

    public function testReturnsEmptyWhenStockAboveIdeal(): void
    {
        $minimum  = $this->makeStockMinimum(idealQuantity: 10);
        $product  = $this->makeProduct();
        $location = $this->makeLocation();

        $stockMinRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $stockMinRepo->method('findAll')->willReturn([$minimum]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('countAvailableByProductAndLocation')->willReturn(15);

        $nonSerializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $poItemRepo = $this->makePoItemRepoMock();

        $handler = new GeneratePurchaseSuggestionsHandler(
            $stockMinRepo,
            $productRepo,
            $locationRepo,
            $serializedRepo,
            $nonSerializedRepo,
            $poItemRepo,
        );

        $result = ($handler)(new GeneratePurchaseSuggestionsQuery());

        self::assertCount(0, $result);
    }

    public function testReturnsEmptyWhenNoMinimums(): void
    {
        $stockMinRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $stockMinRepo->method('findAll')->willReturn([]);

        $handler = new GeneratePurchaseSuggestionsHandler(
            $stockMinRepo,
            $this->createMock(ProductRepositoryInterface::class),
            $this->createMock(LocationRepositoryInterface::class),
            $this->createMock(SerializedStockRepositoryInterface::class),
            $this->createMock(NonserializedStockRepositoryInterface::class),
            $this->makePoItemRepoMock(),
        );

        $result = ($handler)(new GeneratePurchaseSuggestionsQuery());

        self::assertCount(0, $result);
    }

    public function testSkipsWhenProductNotFound(): void
    {
        $minimum = $this->makeStockMinimum();

        $stockMinRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $stockMinRepo->method('findAll')->willReturn([$minimum]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn(null);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);

        $handler = new GeneratePurchaseSuggestionsHandler(
            $stockMinRepo,
            $productRepo,
            $locationRepo,
            $this->createMock(SerializedStockRepositoryInterface::class),
            $this->createMock(NonserializedStockRepositoryInterface::class),
            $this->makePoItemRepoMock(),
        );

        $result = ($handler)(new GeneratePurchaseSuggestionsQuery());

        self::assertCount(0, $result);
    }

    public function testSkipsWhenLocationNotFound(): void
    {
        $minimum = $this->makeStockMinimum();
        $product = $this->makeProduct();

        $stockMinRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $stockMinRepo->method('findAll')->willReturn([$minimum]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn(null);

        $handler = new GeneratePurchaseSuggestionsHandler(
            $stockMinRepo,
            $productRepo,
            $locationRepo,
            $this->createMock(SerializedStockRepositoryInterface::class),
            $this->createMock(NonserializedStockRepositoryInterface::class),
            $this->makePoItemRepoMock(),
        );

        $result = ($handler)(new GeneratePurchaseSuggestionsQuery());

        self::assertCount(0, $result);
    }

    public function testFiltersByLocationId(): void
    {
        $location  = $this->makeLocation();
        $product   = $this->makeProduct();
        $minimum   = $this->makeStockMinimum(locationId: self::LOCATION_ID);

        $stockMinRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $stockMinRepo->expects(self::once())
            ->method('findByLocation')
            ->willReturn([$minimum]);
        $stockMinRepo->expects(self::never())
            ->method('findAll');

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('countAvailableByProductAndLocation')->willReturn(3);

        $nonSerializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $poItemRepo = $this->makePoItemRepoMock();
        $poItemRepo->method('findLastPurchaseDateByProduct')->willReturn(null);

        $handler = new GeneratePurchaseSuggestionsHandler(
            $stockMinRepo,
            $productRepo,
            $locationRepo,
            $serializedRepo,
            $nonSerializedRepo,
            $poItemRepo,
        );

        $result = ($handler)(new GeneratePurchaseSuggestionsQuery(locationId: self::LOCATION_ID));

        self::assertCount(1, $result);
    }

    public function testUsesSerializedStockForSerializedProduct(): void
    {
        $minimum  = $this->makeStockMinimum();
        $product  = $this->makeProduct(true);
        $location = $this->makeLocation();

        $stockMinRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $stockMinRepo->method('findAll')->willReturn([$minimum]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->expects(self::once())
            ->method('countAvailableByProductAndLocation')
            ->willReturn(2);

        $nonSerializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonSerializedRepo->expects(self::never())
            ->method('countAvailableByProductAndLocation');

        $poItemRepo = $this->makePoItemRepoMock();
        $poItemRepo->method('findLastPurchaseDateByProduct')->willReturn(null);

        $handler = new GeneratePurchaseSuggestionsHandler(
            $stockMinRepo,
            $productRepo,
            $locationRepo,
            $serializedRepo,
            $nonSerializedRepo,
            $poItemRepo,
        );

        $result = ($handler)(new GeneratePurchaseSuggestionsQuery());

        self::assertCount(1, $result);
    }

    public function testUsesNonSerializedStockForNonSerializedProduct(): void
    {
        $minimum  = $this->makeStockMinimum();
        $product  = $this->makeProduct(false);
        $location = $this->makeLocation();

        $stockMinRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $stockMinRepo->method('findAll')->willReturn([$minimum]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->expects(self::never())
            ->method('countAvailableByProductAndLocation');

        $nonSerializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonSerializedRepo->expects(self::once())
            ->method('countAvailableByProductAndLocation')
            ->willReturn(1);

        $poItemRepo = $this->makePoItemRepoMock();
        $poItemRepo->method('findLastPurchaseDateByProduct')->willReturn(null);

        $handler = new GeneratePurchaseSuggestionsHandler(
            $stockMinRepo,
            $productRepo,
            $locationRepo,
            $serializedRepo,
            $nonSerializedRepo,
            $poItemRepo,
        );

        $result = ($handler)(new GeneratePurchaseSuggestionsQuery());

        self::assertCount(1, $result);
    }

    public function testSortsByGapDescending(): void
    {
        $minA = $this->makeStockMinimum(self::STOCK_MIN_ID, self::PRODUCT_ID, self::LOCATION_ID, 10, 3);
        $minB = $this->makeStockMinimum(self::STOCK_MIN_ID_2, self::PRODUCT_ID, self::LOCATION_ID_2, 5, 1);

        $product  = $this->makeProduct();
        $locA     = $this->makeLocation(self::LOCATION_ID);
        $locB     = $this->makeLocation(self::LOCATION_ID_2);

        $stockMinRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $stockMinRepo->method('findAll')->willReturn([$minA, $minB]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')
            ->willReturnCallback(static function (LocationIdValueObject $id) use ($locA, $locB): ?Location {
                return match ((string) $id) {
                    self::LOCATION_ID   => $locA,
                    self::LOCATION_ID_2 => $locB,
                    default             => null,
                };
            });

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('countAvailableByProductAndLocation')
            ->willReturnCallback(static function (ProductIdValueObject $productId, LocationIdValueObject $locationId): int {
                return match ((string) $locationId) {
                    self::LOCATION_ID   => 1,
                    self::LOCATION_ID_2 => 4,
                    default             => 0,
                };
            });

        $nonSerializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $poItemRepo = $this->makePoItemRepoMock();
        $poItemRepo->method('findLastPurchaseDateByProduct')->willReturn(null);

        $handler = new GeneratePurchaseSuggestionsHandler(
            $stockMinRepo,
            $productRepo,
            $locationRepo,
            $serializedRepo,
            $nonSerializedRepo,
            $poItemRepo,
        );

        $result = ($handler)(new GeneratePurchaseSuggestionsQuery());

        // minA: ideal=10, current=1 -> gap=9
        // minB: ideal=5, current=4 -> gap=1
        self::assertCount(2, $result);
        self::assertSame(9, $result[0]->gap());
        self::assertSame(1, $result[1]->gap());
    }
}
