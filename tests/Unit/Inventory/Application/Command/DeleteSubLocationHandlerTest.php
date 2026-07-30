<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\DeleteSubLocation\DeleteSubLocationCommand;
use App\Inventory\Application\Command\DeleteSubLocation\DeleteSubLocationHandler;
use App\Inventory\Application\Exception\SubLocationNotFoundException;
use App\Inventory\Domain\Enums\SubLocationType;
use App\Inventory\Domain\Entities\SubLocation;
use App\Inventory\Domain\Repositories\SubLocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use PHPUnit\Framework\TestCase;

final class DeleteSubLocationHandlerTest extends TestCase
{
    private const SUBLOCATION_ID = '01HZ0000000000000000000001';
    private const LOCATION_ID    = '01HZ0000000000000000000002';

    public function testDeletesSubLocation(): void
    {
        $subLocation = SubLocation::create(
            id: SubLocationIdValueObject::fromString(self::SUBLOCATION_ID),
            locationId: self::LOCATION_ID,
            type: SubLocationType::RACK,
            name: 'Sububicación a eliminar',
            code: 'R-001',
        );

        $repo = $this->createMock(SubLocationRepositoryInterface::class);
        $repo->method('findById')->willReturn($subLocation);
        $repo->expects(self::once())->method('delete')->with(SubLocationIdValueObject::fromString(self::SUBLOCATION_ID));

        $handler = new DeleteSubLocationHandler($repo);
        ($handler)(new DeleteSubLocationCommand(id: self::SUBLOCATION_ID));
    }

    public function testThrowsWhenSubLocationNotFound(): void
    {
        $repo = $this->createMock(SubLocationRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new DeleteSubLocationHandler($repo);

        $this->expectException(SubLocationNotFoundException::class);
        ($handler)(new DeleteSubLocationCommand(id: '01HZ0000000000000000000099'));
    }
}
