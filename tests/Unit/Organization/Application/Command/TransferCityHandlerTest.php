<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\TransferCity\TransferCityCommand;
use App\Organization\Application\Command\TransferCity\TransferCityHandler;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class TransferCityHandlerTest extends TestCase
{
    private const SOURCE_ZONE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const TARGET_ZONE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FW';
    private const CITY_ID = '01HZ0000000000000000000001';

    private ServiceZone $sourceZone;
    private ServiceZone $targetZone;

    protected function setUp(): void
    {
        $this->sourceZone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString(self::SOURCE_ZONE_ID),
            name: 'Zona Norte',
        );
        $this->sourceZone->addCity(self::CITY_ID);

        $this->targetZone = ServiceZone::create(
            id: ServiceZoneIdValueObject::fromString(self::TARGET_ZONE_ID),
            name: 'Zona Sur',
        );
    }

    public function testTransfersCityBetweenZones(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturnCallback(function (ServiceZoneIdValueObject $id) {
            $strId = (string) $id;
            return match ($strId) {
                self::SOURCE_ZONE_ID => $this->sourceZone,
                self::TARGET_ZONE_ID => $this->targetZone,
                default => null,
            };
        });
        $repo->expects($this->exactly(2))->method('save');

        $handler = new TransferCityHandler($repo);
        ($handler)(new TransferCityCommand(
            serviceZoneId: self::SOURCE_ZONE_ID,
            cityId: self::CITY_ID,
            targetZoneId: self::TARGET_ZONE_ID,
        ));

        self::assertFalse($this->sourceZone->hasCity(self::CITY_ID));
        self::assertTrue($this->targetZone->hasCity(self::CITY_ID));
    }

    public function testThrowsWhenSourceZoneNotFound(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new TransferCityHandler($repo);

        $this->expectException(ServiceZoneException::class);
        $this->expectExceptionMessage('Zona de servicio origen no encontrada.');

        ($handler)(new TransferCityCommand(
            serviceZoneId: '01HZ0000000000000000000099',
            cityId: self::CITY_ID,
            targetZoneId: self::TARGET_ZONE_ID,
        ));
    }

    public function testThrowsWhenTargetZoneNotFound(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturnCallback(function (ServiceZoneIdValueObject $id) {
            return (string) $id === self::SOURCE_ZONE_ID ? $this->sourceZone : null;
        });

        $handler = new TransferCityHandler($repo);

        $this->expectException(ServiceZoneException::class);
        $this->expectExceptionMessage('Zona de servicio destino no encontrada.');

        ($handler)(new TransferCityCommand(
            serviceZoneId: self::SOURCE_ZONE_ID,
            cityId: self::CITY_ID,
            targetZoneId: '01HZ0000000000000000000099',
        ));
    }

    public function testThrowsWhenSameZone(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn($this->sourceZone);

        $handler = new TransferCityHandler($repo);

        $this->expectException(ServiceZoneException::class);
        $this->expectExceptionMessage('La zona origen y destino no pueden ser la misma.');

        ($handler)(new TransferCityCommand(
            serviceZoneId: self::SOURCE_ZONE_ID,
            cityId: self::CITY_ID,
            targetZoneId: self::SOURCE_ZONE_ID,
        ));
    }

    public function testThrowsWhenCityNotInSource(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturnCallback(function (ServiceZoneIdValueObject $id) {
            return (string) $id === self::TARGET_ZONE_ID ? $this->targetZone : $this->sourceZone;
        });

        $handler = new TransferCityHandler($repo);

        $this->expectException(ServiceZoneException::class);

        ($handler)(new TransferCityCommand(
            serviceZoneId: self::SOURCE_ZONE_ID,
            cityId: '01HZ0000000000000000000099',
            targetZoneId: self::TARGET_ZONE_ID,
        ));
    }
}
