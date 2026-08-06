<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Query;

use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Application\Query\FindServiceZoneById\FindServiceZoneByIdHandler;
use App\Organization\Application\Query\FindServiceZoneById\FindServiceZoneByIdQuery;
use App\Organization\Application\Query\Response\ServiceZoneResponse;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FindServiceZoneByIdHandlerTest extends TestCase
{
    private static function initializeTimestamps(ServiceZone $zone): void
    {
        $ref = new \ReflectionProperty(ServiceZone::class, 'createdAt');
        $ref->setAccessible(true);
        if (!$ref->isInitialized($zone)) {
            $ref->setValue($zone, new DateTimeImmutable());
        }
    }

    private const ZONE_ID   = '01CXZ5NDEKTSV4RRFFQ69G5ABC';
    private const ZONE_NAME = 'Zona Centro';

    private function makeZone(): ServiceZone
    {
        return ServiceZone::create(
            new ServiceZoneIdValueObject(self::ZONE_ID),
            self::ZONE_NAME,
        );
    }

    public function testFindsZoneByIdSuccessfully(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);

        $zone = $this->makeZone();
        self::initializeTimestamps($zone);
        $repo->method('findById')->willReturn($zone);

        $handler = new FindServiceZoneByIdHandler($repo);
        $response = ($handler)(new FindServiceZoneByIdQuery(serviceZoneId: self::ZONE_ID));

        self::assertInstanceOf(ServiceZoneResponse::class, $response);
        self::assertSame(self::ZONE_ID, $response->id);
        self::assertSame(self::ZONE_NAME, $response->name);
        self::assertSame('active', $response->status);
    }

    public function testThrowsWhenZoneNotFound(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new FindServiceZoneByIdHandler($repo);

        $this->expectException(ServiceZoneNotFoundException::class);
        $this->expectExceptionMessage('Zona de servicio con ID "' . self::ZONE_ID . '" no encontrada.');

        ($handler)(new FindServiceZoneByIdQuery(serviceZoneId: self::ZONE_ID));
    }
}
