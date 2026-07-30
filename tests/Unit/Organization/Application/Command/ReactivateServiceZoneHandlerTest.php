<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\ReactivateServiceZone\ReactivateServiceZoneCommand;
use App\Organization\Application\Command\ReactivateServiceZone\ReactivateServiceZoneHandler;
use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Enums\ServiceZoneStatus;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class ReactivateServiceZoneHandlerTest extends TestCase
{
    public function testReactivatesServiceZone(): void
    {
        $zone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString('01JARZ3NDEKTSV4RRFFQ69G5FV'),
            name: 'Zona Norte',
        );
        $zone->deactivate();

        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn($zone);
        $repo->expects($this->once())->method('save');

        $handler = new ReactivateServiceZoneHandler($repo);
        ($handler)(new ReactivateServiceZoneCommand('01JARZ3NDEKTSV4RRFFQ69G5FV'));

        self::assertSame(ServiceZoneStatus::ACTIVE, $zone->status());
    }

    public function testThrowsWhenZoneNotFound(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new ReactivateServiceZoneHandler($repo);

        $this->expectException(ServiceZoneNotFoundException::class);

        ($handler)(new ReactivateServiceZoneCommand('01HZ0000000000000000000099'));
    }
}
