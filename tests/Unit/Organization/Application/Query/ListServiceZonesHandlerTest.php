<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Query;

use App\Organization\Application\Query\ListServiceZones\ListServiceZonesHandler;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesQuery;
use App\Organization\Application\Query\Response\ServiceZoneResponse;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

// Helper to initialize TimestampableInterface properties normally set by Doctrine
function initializeServiceZoneTimestamps(ServiceZone $zone): void
{
    $ref = new \ReflectionProperty(ServiceZone::class, 'createdAt');
    $ref->setAccessible(true);
    if (!$ref->isInitialized($zone)) {
        $ref->setValue($zone, new \DateTimeImmutable());
    }
}

final class ListServiceZonesHandlerTest extends TestCase
{
    public function testReturnsAllZones(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);

        $zone1 = ServiceZone::create(
            new ServiceZoneIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5ABC'),
            'Zona Centro',
        );
        initializeServiceZoneTimestamps($zone1);
        $zone2 = ServiceZone::create(
            new ServiceZoneIdValueObject('01BRZ3NDEKTSV4RRFFQ69G5DEF'),
            'Zona Norte',
        );
        initializeServiceZoneTimestamps($zone2);

        $repo->method('findAll')->willReturn([$zone1, $zone2]);

        $handler = new ListServiceZonesHandler($repo);
        $result = ($handler)(new ListServiceZonesQuery());

        self::assertCount(2, $result);
        self::assertInstanceOf(ServiceZoneResponse::class, $result[0]);
        self::assertInstanceOf(ServiceZoneResponse::class, $result[1]);
        self::assertSame('Zona Centro', $result[0]->name);
        self::assertSame('Zona Norte', $result[1]->name);
    }

    public function testReturnsOnlyActiveZonesWhenFiltered(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);

        $activeZone = ServiceZone::create(
            new ServiceZoneIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5ABC'),
            'Zona Centro',
        );
        initializeServiceZoneTimestamps($activeZone);

        $repo->method('findAllActive')->willReturn([$activeZone]);

        $handler = new ListServiceZonesHandler($repo);
        $result = ($handler)(new ListServiceZonesQuery(onlyActive: true));

        self::assertCount(1, $result);
        self::assertInstanceOf(ServiceZoneResponse::class, $result[0]);
        self::assertSame('Zona Centro', $result[0]->name);
    }

    public function testReturnsEmptyArrayWhenNoZones(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListServiceZonesHandler($repo);
        $result = ($handler)(new ListServiceZonesQuery());

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testForwardsOnlyActiveFlag(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);

        $repo->expects($this->once())
            ->method('findAllActive')
            ->willReturn([]);

        $repo->method('findAll')->willReturn([]);

        $repo->expects($this->never())
            ->method('findAll');

        $handler = new ListServiceZonesHandler($repo);
        ($handler)(new ListServiceZonesQuery(onlyActive: true));
    }
}
