<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entities;

use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Enums\ServiceZoneStatus;
use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class ServiceZoneTest extends TestCase
{
    private const ZONE_ID   = '01CXZ5NDEKTSV4RRFFQ69G5ABC';
    private const VALID_NAME = 'Zona Centro';

    // ── Create ──────────────────────────────────────────────────────────────

    public function testCreateCreatesActiveZone(): void
    {
        $zone = ServiceZone::create(
            new ServiceZoneIdValueObject(self::ZONE_ID),
            self::VALID_NAME,
        );

        self::assertSame(ServiceZoneStatus::ACTIVE, $zone->status());
        self::assertTrue($zone->isActive());
        self::assertSame(self::ZONE_ID, (string) $zone->id());
        self::assertSame(self::VALID_NAME, $zone->name());
        self::assertNotEmpty($zone->name());
        self::assertEmpty($zone->cityIds());
    }

    public function testCreateThrowsWhenNameTooShort(): void
    {
        $this->expectException(ServiceZoneException::class);
        $this->expectExceptionMessage('El nombre de la zona debe tener al menos 3 caracteres.');

        ServiceZone::create(
            new ServiceZoneIdValueObject(self::ZONE_ID),
            'AB',
        );
    }

    public function testCreateThrowsWhenNameTooLong(): void
    {
        $this->expectException(ServiceZoneException::class);
        $this->expectExceptionMessage('El nombre de la zona excede el máximo de 100 caracteres.');

        ServiceZone::create(
            new ServiceZoneIdValueObject(self::ZONE_ID),
            str_repeat('A', 101),
        );
    }

    // ── Change Name ─────────────────────────────────────────────────────────

    public function testChangeNameUpdatesName(): void
    {
        $zone = $this->createActiveZone();

        $zone->changeName('Zona Norte');

        self::assertSame('Zona Norte', $zone->name());
    }

    public function testChangeNameThrowsWhenTooShort(): void
    {
        $zone = $this->createActiveZone();

        $this->expectException(ServiceZoneException::class);

        $zone->changeName('AB');
    }

    // ── Deactivate ──────────────────────────────────────────────────────────

    public function testDeactivateTransitionsToInactive(): void
    {
        $zone = $this->createActiveZone();

        $zone->deactivate();

        self::assertSame(ServiceZoneStatus::INACTIVE, $zone->status());
        self::assertFalse($zone->isActive());
    }

    public function testDeactivateThrowsWhenAlreadyInactive(): void
    {
        $zone = $this->createActiveZone();
        $zone->deactivate();

        $this->expectException(ServiceZoneException::class);
        $this->expectExceptionMessage('La zona ya se encuentra inactiva.');

        $zone->deactivate();
    }

    // ── Reactivate ──────────────────────────────────────────────────────────

    public function testReactivateRestoresActive(): void
    {
        $zone = $this->createActiveZone();
        $zone->deactivate();

        $zone->reactivate();

        self::assertSame(ServiceZoneStatus::ACTIVE, $zone->status());
        self::assertTrue($zone->isActive());
    }

    public function testReactivateThrowsWhenAlreadyActive(): void
    {
        $zone = $this->createActiveZone();

        $this->expectException(ServiceZoneException::class);
        $this->expectExceptionMessage('La zona ya se encuentra activa.');

        $zone->reactivate();
    }

    // ── Add City ────────────────────────────────────────────────────────────

    public function testAddCityAddsCityAndNormalizesToUppercase(): void
    {
        $zone = $this->createActiveZone();

        $zone->addCity('01abc-def-ghi');

        self::assertCount(1, $zone->cityIds());
        self::assertTrue($zone->hasCity('01ABC-DEF-GHI'));
    }

    public function testAddCityIsIdempotent(): void
    {
        $zone = $this->createActiveZone();

        $zone->addCity('01ABC');
        $zone->addCity('01abc'); // mismo ID con diferente casing

        self::assertCount(1, $zone->cityIds());
    }

    public function testAddCityMultipleCities(): void
    {
        $zone = $this->createActiveZone();

        $zone->addCity('01ABC');
        $zone->addCity('02DEF');
        $zone->addCity('03GHI');

        self::assertCount(3, $zone->cityIds());
    }

    // ── Remove City ─────────────────────────────────────────────────────────

    public function testRemoveCityRemovesExistingCity(): void
    {
        $zone = $this->createActiveZone();
        $zone->addCity('01ABC');
        $zone->addCity('02DEF');

        $zone->removeCity('01ABC');

        self::assertCount(1, $zone->cityIds());
        self::assertFalse($zone->hasCity('01ABC'));
        self::assertTrue($zone->hasCity('02DEF'));
    }

    public function testRemoveCityIsIdempotentForNonExistent(): void
    {
        $zone = $this->createActiveZone();

        $zone->removeCity('NONEXISTENT'); // no debe lanzar excepción

        self::assertEmpty($zone->cityIds());
    }

    // ── Has City ────────────────────────────────────────────────────────────

    public function testHasCityReturnsFalseForNonExistent(): void
    {
        $zone = $this->createActiveZone();

        self::assertFalse($zone->hasCity('NONEXISTENT'));
    }

    // ── Soft Delete ─────────────────────────────────────────────────────────

    public function testSoftDeleteSetsDeletedAt(): void
    {
        $zone = $this->createActiveZone();

        $zone->softDelete();

        self::assertNotNull($zone->deletedAt());
        self::assertTrue($zone->isDeleted());
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function createActiveZone(): ServiceZone
    {
        return ServiceZone::create(
            new ServiceZoneIdValueObject(self::ZONE_ID),
            self::VALID_NAME,
        );
    }
}
