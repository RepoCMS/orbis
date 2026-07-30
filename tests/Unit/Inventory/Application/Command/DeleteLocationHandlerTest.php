<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\DeleteLocation\DeleteLocationCommand;
use App\Inventory\Application\Command\DeleteLocation\DeleteLocationHandler;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use PHPUnit\Framework\TestCase;

final class DeleteLocationHandlerTest extends TestCase
{
    private const LOCATION_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testDeletesLocation(): void
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

        $handler = new DeleteLocationHandler($repo);
        ($handler)(new DeleteLocationCommand(id: self::LOCATION_ID));

        self::assertTrue($location->isDeleted());
    }

    public function testThrowsWhenLocationNotFound(): void
    {
        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new DeleteLocationHandler($repo);

        $this->expectException(LocationNotFoundException::class);

        ($handler)(new DeleteLocationCommand(id: '01HZ0000000000000000000099'));
    }
}
