<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\AddCityToZone\AddCityToZoneCommand;
use App\Organization\Application\Command\AddCityToZone\AddCityToZoneHandler;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class AddCityToZoneHandlerTest extends TestCase
{
    private const ZONE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const CITY_ID = 'QC-001';

    public function testAddsCityToZone(): void
    {
        $zone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString(self::ZONE_ID),
            name: 'Zona Norte',
        );

        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn($zone);
        $repo->method('findAllActive')->willReturn([$zone]);

        $repo->expects($this->once())->method('save');

        $handler = new AddCityToZoneHandler($repo);
        ($handler)(new AddCityToZoneCommand(
            serviceZoneId: self::ZONE_ID,
            cityId: self::CITY_ID,
        ));

        self::assertTrue($zone->hasCity(self::CITY_ID));
    }

    public function testThrowsWhenZoneNotFound(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new AddCityToZoneHandler($repo);

        $this->expectException(ServiceZoneException::class);
        $this->expectExceptionMessage('Zona de servicio no encontrada.');

        ($handler)(new AddCityToZoneCommand(
            serviceZoneId: '01HZ0000000000000000000099',
            cityId: self::CITY_ID,
        ));
    }

    public function testThrowsWhenCityAlreadyInAnotherZone(): void
    {
        $zone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString(self::ZONE_ID),
            name: 'Zona Norte',
        );

        $otherZone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString('01HZ0000000000000000000098'),
            name: 'Zona Sur',
        );
        $otherZone->addCity(self::CITY_ID);

        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn($zone);
        $repo->method('findAllActive')->willReturn([$zone, $otherZone]);

        $handler = new AddCityToZoneHandler($repo);

        $this->expectException(ServiceZoneException::class);

        ($handler)(new AddCityToZoneCommand(
            serviceZoneId: self::ZONE_ID,
            cityId: self::CITY_ID,
        ));
    }
}
