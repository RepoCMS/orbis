<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Domain\Entities;

use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    // ─── Factory helpers ─────────────────────────────────────────────────────

    private function makeId(): LocationIdValueObject
    {
        return new LocationIdValueObject('01HZ0000000000000000000001');
    }

    private function makeName(string $name = 'Almacén Central'): LocationNameValueObject
    {
        return new LocationNameValueObject($name);
    }

    private function makeWarehouse(): Location
    {
        return Location::create(
            $this->makeId(),
            $this->makeName(),
            'Guayaquil',
            'Av. Principal 123',
            LocationType::WAREHOUSE,
        );
    }

    private function makeLaboratory(): Location
    {
        return Location::create(
            new LocationIdValueObject('01HZ0000000000000000000002'),
            $this->makeName('Laboratorio Norte'),
            'Quito',
            'Calle Secundaria 456',
            LocationType::LABORATORY,
        );
    }

    // ─── Creation ────────────────────────────────────────────────────────────

    public function testCreateWarehouse(): void
    {
        $location = $this->makeWarehouse();

        self::assertSame('Almacén Central', $location->name()->value());
        self::assertSame('Guayaquil', $location->city());
        self::assertSame('Av. Principal 123', $location->address());
        self::assertSame(LocationType::WAREHOUSE, $location->type());
    }

    public function testCreateLaboratory(): void
    {
        $location = $this->makeLaboratory();

        self::assertSame(LocationType::LABORATORY, $location->type());
    }

    // ─── Type helpers ────────────────────────────────────────────────────────

    public function testIsWarehouseReturnsTrueForWarehouse(): void
    {
        self::assertTrue($this->makeWarehouse()->isWarehouse());
        self::assertFalse($this->makeWarehouse()->isLaboratory());
    }

    public function testIsLaboratoryReturnsTrueForLaboratory(): void
    {
        self::assertTrue($this->makeLaboratory()->isLaboratory());
        self::assertFalse($this->makeLaboratory()->isWarehouse());
    }

    // ─── Soft delete ─────────────────────────────────────────────────────────

    public function testDeleteMarksLocationAsDeleted(): void
    {
        $location = $this->makeWarehouse();
        self::assertFalse($location->isDeleted());

        $location->delete();

        self::assertTrue($location->isDeleted());
    }
}
