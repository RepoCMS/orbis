<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\UpdateLocation\UpdateLocationCommand;
use App\Inventory\Application\Command\UpdateLocation\UpdateLocationHandler;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateLocationHandlerTest extends TestCase
{
    private const LOCATION_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testUpdatesLocation(): void
    {
        $location = Location::create(
            id: new LocationIdValueObject(self::LOCATION_ID),
            name: new LocationNameValueObject('Bodega Norte'),
            city: 'Quito',
            address: 'Av. 1',
            type: LocationType::WAREHOUSE,
        );

        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->method('findById')->willReturn($location);
        $repo->expects($this->once())->method('save');

        $handler = new UpdateLocationHandler($repo);
        ($handler)(new UpdateLocationCommand(
            id: self::LOCATION_ID,
            name: 'Bodega Principal',
            city: 'Guayaquil',
            address: 'Av. 2',
        ));

        self::assertSame('Guayaquil', $location->city());
    }

    public function testThrowsWhenLocationNotFound(): void
    {
        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateLocationHandler($repo);

        $this->expectException(LocationNotFoundException::class);

        ($handler)(new UpdateLocationCommand(
            id: '01HZ0000000000000000000099',
            name: 'Test',
            city: 'Test',
            address: 'Test',
        ));
    }
}
