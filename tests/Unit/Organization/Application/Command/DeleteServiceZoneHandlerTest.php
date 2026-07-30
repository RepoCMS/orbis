<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\DeleteServiceZone\DeleteServiceZoneCommand;
use App\Organization\Application\Command\DeleteServiceZone\DeleteServiceZoneHandler;
use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class DeleteServiceZoneHandlerTest extends TestCase
{
    private const ZONE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testSoftDeletesServiceZone(): void
    {
        $zone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString(self::ZONE_ID),
            name: 'Zona Norte',
        );

        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn($zone);
        $repo->expects($this->once())->method('save');

        $handler = new DeleteServiceZoneHandler($repo);
        ($handler)(new DeleteServiceZoneCommand(self::ZONE_ID));

        self::assertNotNull($zone->deletedAt());
    }

    public function testThrowsWhenZoneNotFound(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new DeleteServiceZoneHandler($repo);

        $this->expectException(ServiceZoneNotFoundException::class);

        ($handler)(new DeleteServiceZoneCommand('01HZ0000000000000000000099'));
    }
}
