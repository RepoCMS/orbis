<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\RemoveCityFromZone\RemoveCityFromZoneCommand;
use App\Organization\Application\Command\RemoveCityFromZone\RemoveCityFromZoneHandler;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class RemoveCityFromZoneHandlerTest extends TestCase
{
    private const ZONE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const CITY_ID = '01HZ0000000000000000000001';

    public function testRemovesCityFromZone(): void
    {
        $zone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString(self::ZONE_ID),
            name: 'Zona Test',
        );
        $zone->addCity(self::CITY_ID);
        self::assertTrue($zone->hasCity(self::CITY_ID));

        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn($zone);
        $repo->expects($this->once())->method('save');

        $handler = new RemoveCityFromZoneHandler($repo);
        ($handler)(new RemoveCityFromZoneCommand(
            serviceZoneId: self::ZONE_ID,
            cityId: self::CITY_ID,
        ));

        self::assertFalse($zone->hasCity(self::CITY_ID));
    }

    public function testThrowsWhenZoneNotFound(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new RemoveCityFromZoneHandler($repo);

        $this->expectException(ServiceZoneException::class);

        ($handler)(new RemoveCityFromZoneCommand(
            serviceZoneId: '01HZ0000000000000000000099',
            cityId: self::CITY_ID,
        ));
    }
}
