<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\UpdateSubLocation\UpdateSubLocationCommand;
use App\Inventory\Application\Command\UpdateSubLocation\UpdateSubLocationHandler;
use App\Inventory\Application\Exception\SubLocationNotFoundException;
use App\Inventory\Domain\Enums\SubLocationType;
use App\Inventory\Domain\Entities\SubLocation;
use App\Inventory\Domain\Repositories\SubLocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateSubLocationHandlerTest extends TestCase
{
    private const SUBLOCATION_ID = '01HZ0000000000000000000001';
    private const LOCATION_ID    = '01HZ0000000000000000000002';

    public function testUpdatesSubLocation(): void
    {
        $subLocation = SubLocation::create(
            id: SubLocationIdValueObject::fromString(self::SUBLOCATION_ID),
            locationId: self::LOCATION_ID,
            type: SubLocationType::RACK,
            name: 'Nombre original',
            code: 'R-001',
        );

        $repo = $this->createMock(SubLocationRepositoryInterface::class);
        $repo->method('findById')->willReturn($subLocation);
        $repo->expects(self::once())->method('save')->with($subLocation);

        $handler = new UpdateSubLocationHandler($repo);
        ($handler)(new UpdateSubLocationCommand(
            id: self::SUBLOCATION_ID,
            name: 'Nombre actualizado',
            code: 'R-002',
        ));

        self::assertSame('Nombre actualizado', $subLocation->name());
    }

    public function testThrowsWhenSubLocationNotFound(): void
    {
        $repo = $this->createMock(SubLocationRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateSubLocationHandler($repo);

        $this->expectException(SubLocationNotFoundException::class);
        ($handler)(new UpdateSubLocationCommand(
            id: '01HZ0000000000000000000099',
            name: 'Test',
            code: 'X',
        ));
    }
}
