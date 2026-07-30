<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\DeactivateServiceZone\DeactivateServiceZoneCommand;
use App\Organization\Application\Command\DeactivateServiceZone\DeactivateServiceZoneHandler;
use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Enums\ServiceZoneStatus;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class DeactivateServiceZoneHandlerTest extends TestCase
{
    private const ZONE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testDeactivatesServiceZone(): void
    {
        $zone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString(self::ZONE_ID),
            name: 'Zona Norte',
        );

        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn($zone);
        $repo->expects($this->once())->method('save');

        $handler = new DeactivateServiceZoneHandler($repo);
        ($handler)(new DeactivateServiceZoneCommand(self::ZONE_ID));

        self::assertSame(ServiceZoneStatus::INACTIVE, $zone->status());
    }

    public function testThrowsWhenZoneNotFound(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new DeactivateServiceZoneHandler($repo);

        $this->expectException(ServiceZoneNotFoundException::class);

        ($handler)(new DeactivateServiceZoneCommand('01HZ0000000000000000000099'));
    }
}
