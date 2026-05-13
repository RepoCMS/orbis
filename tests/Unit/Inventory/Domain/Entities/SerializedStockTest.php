<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Domain\Entities;

use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\SerializedStockStatus;
use App\Inventory\Domain\Exceptions\StockReservedException;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use PHPUnit\Framework\TestCase;

final class SerializedStockTest extends TestCase
{
    // ─── Factory helpers ─────────────────────────────────────────────────────

    private function makeStock(
        ?LocationIdValueObject $location = null,
        string $serial = 'SN-001',
    ): SerializedStock {
        return SerializedStock::register(
            new SerializedStockIdValueObject('01HZ0000000000000000000001'),
            new ProductIdValueObject('01HZ0000000000000000000002'),
            new StockSerialNumberValueObject($serial),
            $location ?? new LocationIdValueObject('01HZ0000000000000000000003'),
        );
    }

    private function makeLocation(string $id = '01HZ0000000000000000000003'): LocationIdValueObject
    {
        return new LocationIdValueObject($id);
    }

    // ─── Registration ────────────────────────────────────────────────────────

    public function testRegisterCreatesAvailableStock(): void
    {
        $stock = $this->makeStock();

        self::assertSame(SerializedStockStatus::AVAILABLE, $stock->status());
        self::assertTrue($stock->isAvailable());
    }

    public function testRegisterStoresSerialNumber(): void
    {
        $stock = $this->makeStock(serial: 'SN-ABC-123');
        self::assertSame('SN-ABC-123', (string) $stock->serialNumber());
    }

    // ─── Reserve ─────────────────────────────────────────────────────────────

    public function testReserveSetsStatusToReserved(): void
    {
        $stock = $this->makeStock();
        $stock->reserve();

        self::assertSame(SerializedStockStatus::RESERVED, $stock->status());
    }

    public function testReserveAlreadyReservedThrowsException(): void
    {
        $stock = $this->makeStock();
        $stock->reserve();

        $this->expectException(StockReservedException::class);
        $stock->reserve();
    }

    public function testReserveDamagedThrowsException(): void
    {
        $stock = $this->makeStock();
        $stock->markDamaged();

        $this->expectException(StockReservedException::class);
        $stock->reserve();
    }

    // ─── Release ─────────────────────────────────────────────────────────────

    public function testReleaseMakesStockAvailableAgain(): void
    {
        $stock = $this->makeStock();
        $stock->reserve();
        $stock->release();

        self::assertSame(SerializedStockStatus::AVAILABLE, $stock->status());
    }

    // ─── Transfer ────────────────────────────────────────────────────────────

    public function testCompleteTransferUpdatesLocation(): void
    {
        $stock       = $this->makeStock();
        $destination = $this->makeLocation('01HZ0000000000000000000004');

        $stock->reserve();
        $stock->completeTransfer($destination);

        self::assertSame($destination, $stock->locationId());
        self::assertSame(SerializedStockStatus::AVAILABLE, $stock->status());
    }

    // ─── Damage / Decommission ────────────────────────────────────────────────

    public function testMarkDamagedSetsStatus(): void
    {
        $stock = $this->makeStock();
        $stock->markDamaged();

        self::assertSame(SerializedStockStatus::DAMAGED, $stock->status());
    }

    public function testDecommissionSetsStatus(): void
    {
        $stock = $this->makeStock();
        $stock->decommission();

        self::assertSame(SerializedStockStatus::DECOMMISSIONED, $stock->status());
    }
}
