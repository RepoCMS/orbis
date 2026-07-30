<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Application\Query\Response\LocationResponse;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use PHPUnit\Framework\TestCase;

final class ListLocationsHandlerTest extends TestCase
{
    private const LOCATION_ID_1 = '01HZ0000000000000000000001';
    private const LOCATION_ID_2 = '01HZ0000000000000000000002';
    private const LOCATION_ID_3 = '01HZ0000000000000000000003';

    private function makeLocation(string $id, string $name, string $city, string $address, LocationType $type): Location
    {
        return Location::create(
            new LocationIdValueObject($id),
            new LocationNameValueObject($name),
            $city,
            $address,
            $type,
        );
    }

    private function makeHandler(array $locations): ListLocationsHandler
    {
        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->method('findAll')->willReturn($locations);

        return new ListLocationsHandler($repo);
    }

    public function testReturnsAllLocations(): void
    {
        $loc1 = $this->makeLocation(
            self::LOCATION_ID_1,
            'Almacén Central',
            'Guayaquil',
            'Av. Principal 123',
            LocationType::WAREHOUSE,
        );
        $loc2 = $this->makeLocation(
            self::LOCATION_ID_2,
            'Lab Norte',
            'Quito',
            'Calle Secundaria 456',
            LocationType::LABORATORY,
        );

        $handler = $this->makeHandler([$loc1, $loc2]);
        $result  = ($handler)();

        self::assertCount(2, $result);
        self::assertInstanceOf(LocationResponse::class, $result[0]);
        self::assertInstanceOf(LocationResponse::class, $result[1]);
    }

    public function testReturnsEmptyArrayWhenNoLocations(): void
    {
        $handler = $this->makeHandler([]);
        $result  = ($handler)();

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $location = $this->makeLocation(
            self::LOCATION_ID_1,
            'Almacén Central',
            'Guayaquil',
            'Av. Principal 123',
            LocationType::WAREHOUSE,
        );

        $handler = $this->makeHandler([$location]);
        $result  = ($handler)();

        self::assertCount(1, $result);
        self::assertSame(self::LOCATION_ID_1, $result[0]->id);
        self::assertSame('Almacén Central', $result[0]->name);
        self::assertSame('Guayaquil', $result[0]->city);
        self::assertSame('Av. Principal 123', $result[0]->address);
        self::assertSame('warehouse', $result[0]->type);
        self::assertSame('Almacén', $result[0]->typeLabel);
    }

    public function testMapsAllLocationTypes(): void
    {
        $warehouse = $this->makeLocation(
            self::LOCATION_ID_1,
            'Almacén Central',
            'Guayaquil',
            'Av. Principal',
            LocationType::WAREHOUSE,
        );
        $lab = $this->makeLocation(
            self::LOCATION_ID_2,
            'Laboratorio',
            'Quito',
            'Calle Secundaria',
            LocationType::LABORATORY,
        );
        $backpack = $this->makeLocation(
            self::LOCATION_ID_3,
            'Mochila Técnico',
            'Cuenca',
            'Campo',
            LocationType::BACKPACK,
        );

        $handler = $this->makeHandler([$warehouse, $lab, $backpack]);
        $result  = ($handler)();

        self::assertCount(3, $result);
        self::assertSame('warehouse', $result[0]->type);
        self::assertSame('Almacén', $result[0]->typeLabel);
        self::assertSame('laboratory', $result[1]->type);
        self::assertSame('Laboratorio', $result[1]->typeLabel);
        self::assertSame('backpack', $result[2]->type);
        self::assertSame('Mochila', $result[2]->typeLabel);
    }
}
