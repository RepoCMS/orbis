<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\Response\NonserializedStockResponse;
use App\Inventory\Application\Query\Response\SerializedStockResponse;
use App\Inventory\Application\Query\StockByProduct\StockByProductHandler;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Enums\SerializedStockStatus;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use PHPUnit\Framework\TestCase;

final class StockByProductHandlerTest extends TestCase
{
    private const PRODUCT_ID   = '01HZ0000000000000000000001';
    private const SERIALIZED_1 = '01HZ0000000000000000000010';
    private const SERIALIZED_2 = '01HZ0000000000000000000011';
    private const NONSERIALIZED = '01HZ0000000000000000000020';
    private const LOCATION_A   = '01HZ0000000000000000000030';
    private const LOCATION_B   = '01HZ0000000000000000000031';

    private function makeSerializedStock(
        string $id,
        string $locationId,
        SerializedStockStatus $status = SerializedStockStatus::AVAILABLE,
    ): SerializedStock {
        $stock = SerializedStock::register(
            new SerializedStockIdValueObject($id),
            new ProductIdValueObject(self::PRODUCT_ID),
            new StockSerialNumberValueObject('SN-' . $id),
            new LocationIdValueObject($locationId),
        );

        match ($status) {
            SerializedStockStatus::AVAILABLE => null,
            SerializedStockStatus::RESERVED  => $stock->reserve(),
            SerializedStockStatus::DAMAGED   => $stock->markDamaged(),
            SerializedStockStatus::SUSPECT   => $stock->markSuspect(),
            SerializedStockStatus::DECOMMISSIONED => $stock->decommission(),
        };

        return $stock;
    }

    private function makeNonserializedStock(
        string $id,
        string $locationId,
        int $quantity = 5,
        NonserializedStockStatus $status = NonserializedStockStatus::AVAILABLE,
    ): NonserializedStock {
        $stock = NonserializedStock::initialize(
            new NonserializedStockIdValueObject($id),
            new ProductIdValueObject(self::PRODUCT_ID),
            new LocationIdValueObject($locationId),
            $quantity,
        );

        match ($status) {
            NonserializedStockStatus::AVAILABLE => null,
            NonserializedStockStatus::RESERVED  => $stock->reserve(),
            NonserializedStockStatus::DAMAGED   => $stock->markDamaged(),
            NonserializedStockStatus::DECOMMISSIONED => $stock->decommission(),
        };

        return $stock;
    }

    private function makeLocation(string $id, string $name): Location
    {
        return Location::create(
            new LocationIdValueObject($id),
            new LocationNameValueObject($name),
            'Guayaquil',
            'Av. Principal 123',
            LocationType::WAREHOUSE,
        );
    }

    private function makeHandler(
        array $serialized,
        array $nonserialized,
        array $locations,
    ): StockByProductHandler {
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findByProduct')->willReturn($serialized);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProduct')->willReturn($nonserialized);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findAll')->willReturn($locations);

        return new StockByProductHandler($serializedRepo, $nonserializedRepo, $locationRepo);
    }

    public function testReturnsBothSerializedAndNonserialized(): void
    {
        $locA = $this->makeLocation(self::LOCATION_A, 'Almacén Central');
        $s1   = $this->makeSerializedStock(self::SERIALIZED_1, self::LOCATION_A);
        $ns1  = $this->makeNonserializedStock(self::NONSERIALIZED, self::LOCATION_A);

        $handler = $this->makeHandler([$s1], [$ns1], [$locA]);
        $result  = ($handler)(self::PRODUCT_ID);

        self::assertArrayHasKey('serialized', $result);
        self::assertArrayHasKey('nonserialized', $result);
        self::assertCount(1, $result['serialized']);
        self::assertCount(1, $result['nonserialized']);
        self::assertInstanceOf(SerializedStockResponse::class, $result['serialized'][0]);
        self::assertInstanceOf(NonserializedStockResponse::class, $result['nonserialized'][0]);
    }

    public function testReturnsEmptyArraysWhenNoStock(): void
    {
        $handler = $this->makeHandler([], [], []);
        $result  = ($handler)(self::PRODUCT_ID);

        self::assertArrayHasKey('serialized', $result);
        self::assertArrayHasKey('nonserialized', $result);
        self::assertCount(0, $result['serialized']);
        self::assertCount(0, $result['nonserialized']);
    }

    public function testSerializedResponseContainsExpectedFields(): void
    {
        $locA = $this->makeLocation(self::LOCATION_A, 'Almacén Central');
        $s1   = $this->makeSerializedStock(self::SERIALIZED_1, self::LOCATION_A);

        $handler = $this->makeHandler([$s1], [], [$locA]);
        $result  = ($handler)(self::PRODUCT_ID);

        self::assertCount(1, $result['serialized']);
        self::assertSame(self::SERIALIZED_1, $result['serialized'][0]->id);
        self::assertSame(self::PRODUCT_ID, $result['serialized'][0]->productId);
        self::assertSame('SN-' . self::SERIALIZED_1, $result['serialized'][0]->serialNumber);
        self::assertSame(self::LOCATION_A, $result['serialized'][0]->locationId);
        self::assertSame('Almacén Central', $result['serialized'][0]->locationName);
        self::assertSame('disponible', $result['serialized'][0]->status);
    }

    public function testNonserializedResponseContainsExpectedFields(): void
    {
        $locA = $this->makeLocation(self::LOCATION_A, 'Almacén Central');
        $ns1  = $this->makeNonserializedStock(self::NONSERIALIZED, self::LOCATION_A, 10);

        $handler = $this->makeHandler([], [$ns1], [$locA]);
        $result  = ($handler)(self::PRODUCT_ID);

        self::assertCount(1, $result['nonserialized']);
        self::assertSame(self::NONSERIALIZED, $result['nonserialized'][0]->id);
        self::assertSame(self::PRODUCT_ID, $result['nonserialized'][0]->productId);
        self::assertSame(self::LOCATION_A, $result['nonserialized'][0]->locationId);
        self::assertSame('Almacén Central', $result['nonserialized'][0]->locationName);
        self::assertSame(10, $result['nonserialized'][0]->quantity);
        self::assertSame('disponible', $result['nonserialized'][0]->status);
    }

    public function testUsesLocationIdWhenLocationNotFoundInMap(): void
    {
        $s1  = $this->makeSerializedStock(self::SERIALIZED_1, self::LOCATION_A);
        $ns1 = $this->makeNonserializedStock(self::NONSERIALIZED, self::LOCATION_B, 3);

        $locA = $this->makeLocation(self::LOCATION_A, 'Almacén Central');
        $handler = $this->makeHandler([$s1], [$ns1], [$locA]);
        $result  = ($handler)(self::PRODUCT_ID);

        // LOCATION_A is in map -> resolved name
        self::assertSame('Almacén Central', $result['serialized'][0]->locationName);
        // LOCATION_B is NOT in map -> falls back to ID
        self::assertSame(self::LOCATION_B, $result['nonserialized'][0]->locationName);
    }

    public function testMapsAllSerializedStatuses(): void
    {
        $locA = $this->makeLocation(self::LOCATION_A, 'Almacén');
        $s1 = $this->makeSerializedStock('01HZ00000000000000000000a1', self::LOCATION_A, SerializedStockStatus::AVAILABLE);
        $s2 = $this->makeSerializedStock('01HZ00000000000000000000a2', self::LOCATION_A, SerializedStockStatus::RESERVED);
        $s3 = $this->makeSerializedStock('01HZ00000000000000000000a3', self::LOCATION_A, SerializedStockStatus::DAMAGED);
        $s4 = $this->makeSerializedStock('01HZ00000000000000000000a4', self::LOCATION_A, SerializedStockStatus::SUSPECT);

        $handler = $this->makeHandler([$s1, $s2, $s3, $s4], [], [$locA]);
        $result  = ($handler)(self::PRODUCT_ID);

        self::assertCount(4, $result['serialized']);
        self::assertSame('disponible', $result['serialized'][0]->status);
        self::assertSame('reservado', $result['serialized'][1]->status);
        self::assertSame('dañado', $result['serialized'][2]->status);
        self::assertSame('sospechoso', $result['serialized'][3]->status);
    }

    public function testMapsAllNonserializedStatuses(): void
    {
        $locA = $this->makeLocation(self::LOCATION_A, 'Almacén');
        $ns1 = $this->makeNonserializedStock('01HZ00000000000000000000b1', self::LOCATION_A, 5, NonserializedStockStatus::AVAILABLE);
        $ns2 = $this->makeNonserializedStock('01HZ00000000000000000000b2', self::LOCATION_A, 3, NonserializedStockStatus::RESERVED);
        $ns3 = $this->makeNonserializedStock('01HZ00000000000000000000b3', self::LOCATION_A, 2, NonserializedStockStatus::DAMAGED);

        $handler = $this->makeHandler([], [$ns1, $ns2, $ns3], [$locA]);
        $result  = ($handler)(self::PRODUCT_ID);

        self::assertCount(3, $result['nonserialized']);
        self::assertSame('disponible', $result['nonserialized'][0]->status);
        self::assertSame('reservado', $result['nonserialized'][1]->status);
        self::assertSame('dañado', $result['nonserialized'][2]->status);
    }
}
