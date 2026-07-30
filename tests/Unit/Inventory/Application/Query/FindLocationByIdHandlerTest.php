<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Query\FindLocationById\FindLocationByIdHandler;
use App\Inventory\Application\Query\Response\LocationResponse;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use PHPUnit\Framework\TestCase;

final class FindLocationByIdHandlerTest extends TestCase
{
    private const LOCATION_ID = '01HZ0000000000000000000002';

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

    private function makeHandler(?Location $location = null): FindLocationByIdHandler
    {
        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->method('findById')->willReturn($location);

        return new FindLocationByIdHandler($repo);
    }

    public function testReturnsLocationResponseWhenFound(): void
    {
        $handler = $this->makeHandler($this->makeLocation());

        $response = ($handler)(self::LOCATION_ID);

        self::assertInstanceOf(LocationResponse::class, $response);
        self::assertSame(self::LOCATION_ID, $response->id);
        self::assertSame('Almacén Central', $response->name);
        self::assertSame('Guayaquil', $response->city);
        self::assertSame('Av. Principal 123', $response->address);
        self::assertSame(LocationType::WAREHOUSE->value, $response->type);
        self::assertSame('Almacén', $response->typeLabel);
    }

    public function testThrowsExceptionWhenNotFound(): void
    {
        $handler = $this->makeHandler(null);

        $this->expectException(LocationNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Ubicación con ID "%s" no encontrada.', self::LOCATION_ID));

        ($handler)(self::LOCATION_ID);
    }

    public function testReturnsLocationResponseForLaboratory(): void
    {
        $location = Location::create(
            new LocationIdValueObject('01HZ0000000000000000000003'),
            new LocationNameValueObject('Laboratorio Principal'),
            'Quito',
            'Av. Siempre Viva 456',
            LocationType::LABORATORY,
        );

        $handler = $this->makeHandler($location);

        $response = ($handler)('01HZ0000000000000000000003');

        self::assertSame(LocationType::LABORATORY->value, $response->type);
        self::assertSame('Laboratorio', $response->typeLabel);
    }

    public function testReturnsLocationResponseForBackpack(): void
    {
        $location = Location::create(
            new LocationIdValueObject('01HZ0000000000000000000004'),
            new LocationNameValueObject('Mochila Juan'),
            'Cuenca',
            'Calle 123',
            LocationType::BACKPACK,
        );

        $handler = $this->makeHandler($location);

        $response = ($handler)('01HZ0000000000000000000004');

        self::assertSame(LocationType::BACKPACK->value, $response->type);
        self::assertSame('Mochila', $response->typeLabel);
    }
}
