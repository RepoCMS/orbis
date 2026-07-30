<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\UpdateServiceZone\UpdateServiceZoneCommand;
use App\Organization\Application\Command\UpdateServiceZone\UpdateServiceZoneHandler;
use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateServiceZoneHandlerTest extends TestCase
{
    private const ZONE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testUpdatesServiceZone(): void
    {
        $zone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString(self::ZONE_ID),
            name: 'Zona Norte',
        );

        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn($zone);
        $repo->method('existsByName')->willReturn(false);
        $repo->expects($this->once())->method('save');

        $handler = new UpdateServiceZoneHandler($repo);
        ($handler)(new UpdateServiceZoneCommand(
            serviceZoneId: self::ZONE_ID,
            name: 'Zona Centro-Norte',
        ));

        self::assertSame('Zona Centro-Norte', $zone->name());
    }

    public function testThrowsWhenZoneNotFound(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateServiceZoneHandler($repo);

        $this->expectException(ServiceZoneNotFoundException::class);

        ($handler)(new UpdateServiceZoneCommand(
            serviceZoneId: '01HZ0000000000000000000099',
            name: 'Test',
        ));
    }

    public function testThrowsWhenNameAlreadyExists(): void
    {
        $zone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString(self::ZONE_ID),
            name: 'Zona Norte',
        );

        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn($zone);
        $repo->method('existsByName')->willReturn(true);

        $handler = new UpdateServiceZoneHandler($repo);

        $this->expectException(ServiceZoneException::class);

        ($handler)(new UpdateServiceZoneCommand(
            serviceZoneId: self::ZONE_ID,
            name: 'Zona Existente',
        ));
    }
}
