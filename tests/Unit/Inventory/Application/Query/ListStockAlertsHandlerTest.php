<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListStockAlerts\ListStockAlertsHandler;
use App\Inventory\Application\Query\ListStockAlerts\ListStockAlertsQuery;
use App\Inventory\Application\Query\Response\StockAlertResponse;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Entities\StockMinimum;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListStockAlertsHandlerTest extends TestCase
{
    private const PRODUCT_ID_SERIAL   = '01HZ0000000000000000000002';
    private const PRODUCT_ID_NONSER   = '01HZ0000000000000000000004';
    private const LOCATION_ID         = '01HZ0000000000000000000003';

    private function makeSerializedProduct(): Product
    {
        return Product::add(
            new ProductIdValueObject(self::PRODUCT_ID_SERIAL),
            new ProductCodeAssignedBySupplierValueObject('CODE-S'),
            new ProductNameValueObject('ATM Display'),
            'Desc',
            ProductTrackingType::SERIALIZED,
        );
    }

    private function makeNonSerializedProduct(): Product
    {
        return Product::add(
            new ProductIdValueObject(self::PRODUCT_ID_NONSER),
            new ProductCodeAssignedBySupplierValueObject('CODE-N'),
            new ProductNameValueObject('Papel Térmico'),
            'Desc',
            ProductTrackingType::NON_SERIALIZED,
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

    private function makeMinimum(
        string $id,
        string $productId,
        int    $idealQuantity  = 10,
        int    $alertThreshold = 3,
    ): StockMinimum {
        return StockMinimum::define(
            id:             new StockMinimumIdValueObject($id),
            productId:      new ProductIdValueObject($productId),
            locationId:     new LocationIdValueObject(self::LOCATION_ID),
            idealQuantity:  $idealQuantity,
            alertThreshold: $alertThreshold,
        );
    }

    private function makeHandler(
        array $minimums,
        array $productMap,
        int   $serializedCount    = 0,
        int   $nonSerializedCount = 0,
        bool  $useLocationFilter  = false,
    ): ListStockAlertsHandler {
        $minimumRepo  = $this->createMock(StockMinimumRepositoryInterface::class);
        $productRepo  = $this->createMock(ProductRepositoryInterface::class);
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $serialRepo   = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonSerRepo   = $this->createMock(NonserializedStockRepositoryInterface::class);

        if ($useLocationFilter) {
            $minimumRepo->method('findByLocation')->willReturn($minimums);
        } else {
            $minimumRepo->method('findAll')->willReturn($minimums);
        }

        $productRepo->method('findById')->willReturnCallback(
            fn (ProductIdValueObject $id) => $productMap[(string) $id] ?? null
        );

        $locationRepo->method('findById')->willReturn($this->makeLocation());
        $serialRepo->method('countAvailableByProductAndLocation')->willReturn($serializedCount);
        $nonSerRepo->method('countAvailableByProductAndLocation')->willReturn($nonSerializedCount);

        return new ListStockAlertsHandler(
            $minimumRepo,
            $productRepo,
            $locationRepo,
            $serialRepo,
            $nonSerRepo,
        );
    }

    // ─── Serialized product below threshold ───────────────────────────────────

    public function testReturnsAlertForSerializedProductBelowThreshold(): void
    {
        $minimum  = $this->makeMinimum('01HZ0000000000000000000010', self::PRODUCT_ID_SERIAL, 10, 3);
        $product  = $this->makeSerializedProduct();

        $handler = $this->makeHandler(
            minimums:         [$minimum],
            productMap:       [self::PRODUCT_ID_SERIAL => $product],
            serializedCount:  2,   // 2 ≤ 3 → below threshold
        );

        $alerts = ($handler)(new ListStockAlertsQuery());

        self::assertCount(1, $alerts);
        self::assertInstanceOf(StockAlertResponse::class, $alerts[0]);
        self::assertSame(2, $alerts[0]->currentStock);
        self::assertSame(8, $alerts[0]->gap); // 10 - 2
        self::assertSame('ATM Display', $alerts[0]->productName);
        self::assertSame('Almacén Central', $alerts[0]->locationName);
    }

    // ─── Non-serialized product below threshold ───────────────────────────────

    public function testReturnsAlertForNonSerializedProductBelowThreshold(): void
    {
        $minimum = $this->makeMinimum('01HZ0000000000000000000011', self::PRODUCT_ID_NONSER, 20, 5);
        $product = $this->makeNonSerializedProduct();

        $handler = $this->makeHandler(
            minimums:             [$minimum],
            productMap:           [self::PRODUCT_ID_NONSER => $product],
            nonSerializedCount:   4,   // 4 ≤ 5 → below threshold
        );

        $alerts = ($handler)(new ListStockAlertsQuery());

        self::assertCount(1, $alerts);
        self::assertSame(4, $alerts[0]->currentStock);
        self::assertSame(16, $alerts[0]->gap); // 20 - 4
    }

    // ─── Stock above threshold → no alert ─────────────────────────────────────

    public function testDoesNotReturnAlertWhenStockAboveThreshold(): void
    {
        $minimum = $this->makeMinimum('01HZ0000000000000000000010', self::PRODUCT_ID_SERIAL, 10, 3);
        $product = $this->makeSerializedProduct();

        $handler = $this->makeHandler(
            minimums:        [$minimum],
            productMap:      [self::PRODUCT_ID_SERIAL => $product],
            serializedCount: 5,   // 5 > 3 → OK
        );

        $alerts = ($handler)(new ListStockAlertsQuery());

        self::assertCount(0, $alerts);
    }

    // ─── Stock exactly at threshold → alert ──────────────────────────────────

    public function testReturnsAlertWhenStockEqualsThreshold(): void
    {
        $minimum = $this->makeMinimum('01HZ0000000000000000000010', self::PRODUCT_ID_SERIAL, 10, 3);
        $product = $this->makeSerializedProduct();

        $handler = $this->makeHandler(
            minimums:        [$minimum],
            productMap:      [self::PRODUCT_ID_SERIAL => $product],
            serializedCount: 3,   // 3 == 3 → alert
        );

        $alerts = ($handler)(new ListStockAlertsQuery());

        self::assertCount(1, $alerts);
    }

    // ─── Sorted by gap descending ─────────────────────────────────────────────

    public function testAlertsSortedByGapDescending(): void
    {
        $m1 = $this->makeMinimum('01HZ0000000000000000000010', self::PRODUCT_ID_SERIAL, 10, 3); // gap=8 (stock=2)
        $m2 = $this->makeMinimum('01HZ0000000000000000000011', self::PRODUCT_ID_NONSER, 20, 5); // gap=18 (stock=2)

        $serialRepo  = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonSerRepo  = $this->createMock(NonserializedStockRepositoryInterface::class);
        $minimumRepo = $this->createMock(StockMinimumRepositoryInterface::class);
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);

        $minimumRepo->method('findAll')->willReturn([$m1, $m2]);
        $productRepo->method('findById')->willReturnCallback(function (ProductIdValueObject $id) {
            return match ((string) $id) {
                self::PRODUCT_ID_SERIAL => $this->makeSerializedProduct(),
                self::PRODUCT_ID_NONSER => $this->makeNonSerializedProduct(),
                default                 => null,
            };
        });
        $locationRepo->method('findById')->willReturn($this->makeLocation());
        $serialRepo->method('countAvailableByProductAndLocation')->willReturn(2);
        $nonSerRepo->method('countAvailableByProductAndLocation')->willReturn(2);

        $handler = new ListStockAlertsHandler(
            $minimumRepo,
            $productRepo,
            $locationRepo,
            $serialRepo,
            $nonSerRepo,
        );

        $alerts = ($handler)(new ListStockAlertsQuery());

        self::assertCount(2, $alerts);
        // m2 has gap=18, m1 has gap=8 → m2 first
        self::assertSame(18, $alerts[0]->gap);
        self::assertSame(8, $alerts[1]->gap);
    }

    // ─── Location filter ──────────────────────────────────────────────────────

    public function testFiltersAlertsByLocation(): void
    {
        $minimum = $this->makeMinimum('01HZ0000000000000000000010', self::PRODUCT_ID_SERIAL, 10, 3);
        $product = $this->makeSerializedProduct();

        $handler = $this->makeHandler(
            minimums:        [$minimum],
            productMap:      [self::PRODUCT_ID_SERIAL => $product],
            serializedCount: 1,
            useLocationFilter: true,
        );

        $alerts = ($handler)(new ListStockAlertsQuery(locationId: self::LOCATION_ID));

        self::assertCount(1, $alerts);
    }

    // ─── Skips when product not found ────────────────────────────────────────

    public function testSkipsMinimumWhenProductNotFound(): void
    {
        $minimum = $this->makeMinimum('01HZ0000000000000000000010', self::PRODUCT_ID_SERIAL, 10, 3);

        $handler = $this->makeHandler(
            minimums:   [$minimum],
            productMap: [],   // product not found
        );

        $alerts = ($handler)(new ListStockAlertsQuery());

        self::assertCount(0, $alerts);
    }
}
