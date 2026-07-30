<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\Entities;

use App\Client\Domain\Entities\Site;
use App\Client\Domain\Enums\SiteStatus;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class SiteTest extends TestCase
{
    private function createSite(?ServiceZoneIdValueObject $zoneId = null): Site
    {
        return Site::add(
            new SiteIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            '01CYZ5NDEKTSV4RRFFQ69G5XYZ',
            new TerminalIdValueObject('01DEG6NDEKTSV4RRFFQ69G5DEF'),
            'Sucursal Centro',
            SiteStatus::ACTIVE,
            $zoneId,
        );
    }

    public function testAddStoresAllFields(): void
    {
        $site = $this->createSite();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $site->id());
        self::assertSame('01BXZ4MDEKTSV4RRFFQ69G5ABC', (string) $site->clientId());
        self::assertSame('01CYZ5NDEKTSV4RRFFQ69G5XYZ', $site->cityId());
        self::assertSame('01DEG6NDEKTSV4RRFFQ69G5DEF', (string) $site->terminalId());
        self::assertSame('Sucursal Centro', $site->name());
        self::assertSame(SiteStatus::ACTIVE, $site->status());
        self::assertNull($site->serviceZoneId());
        self::assertFalse($site->isDeleted());
    }

    public function testAddWithServiceZone(): void
    {
        $zoneId = new ServiceZoneIdValueObject('01EFG7NDEKTSV4RRFFQ69G5HBJ');
        $site = $this->createSite($zoneId);

        self::assertSame('01EFG7NDEKTSV4RRFFQ69G5HBJ', (string) $site->serviceZoneId());
    }

    public function testAddWithInactiveStatus(): void
    {
        $site = Site::add(
            new SiteIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            '01CYZ5NDEKTSV4RRFFQ69G5XYZ',
            new TerminalIdValueObject('01DEG6NDEKTSV4RRFFQ69G5DEF'),
            'Sucursal Centro',
            SiteStatus::INACTIVE,
        );

        self::assertSame(SiteStatus::INACTIVE, $site->status());
    }

    public function testChangeNameUpdatesValue(): void
    {
        $site = $this->createSite();

        $site->changeName('Sucursal Norte');

        self::assertSame('Sucursal Norte', $site->name());
    }

    public function testChangeNameThrowsWhenEmpty(): void
    {
        $site = $this->createSite();

        $this->expectException(\InvalidArgumentException::class);

        $site->changeName('  ');
    }

    public function testChangeNameTrimsValue(): void
    {
        $site = $this->createSite();

        $site->changeName('  Sucursal Norte  ');

        self::assertSame('Sucursal Norte', $site->name());
    }

    public function testAssignToZoneSetsZone(): void
    {
        $site = $this->createSite();
        $zoneId = new ServiceZoneIdValueObject('01EFG7NDEKTSV4RRFFQ69G5HBJ');

        $site->assignToZone($zoneId);

        self::assertSame('01EFG7NDEKTSV4RRFFQ69G5HBJ', (string) $site->serviceZoneId());
    }

    public function testRemoveFromZoneClearsZone(): void
    {
        $zoneId = new ServiceZoneIdValueObject('01EFG7NDEKTSV4RRFFQ69G5HBJ');
        $site = $this->createSite($zoneId);

        $site->removeFromZone();

        self::assertNull($site->serviceZoneId());
    }

    public function testDeactivateSetsInactiveStatus(): void
    {
        $site = $this->createSite();

        $site->deactivate();

        self::assertSame(SiteStatus::INACTIVE, $site->status());
    }

    public function testReactivateSetsActiveStatus(): void
    {
        $site = $this->createSite();
        $site->deactivate();

        $site->reactivate();

        self::assertSame(SiteStatus::ACTIVE, $site->status());
    }

    public function testSoftDeleteSetsDeletedAt(): void
    {
        $site = $this->createSite();

        self::assertFalse($site->isDeleted());

        $site->softDelete();

        self::assertTrue($site->isDeleted());
        self::assertNotNull($site->deletedAt());
    }
}
