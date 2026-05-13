<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Domain\Entities;

use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Exceptions\InsufficientStockException;
use App\Inventory\Domain\Exceptions\StockReservedException;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use PHPUnit\Framework\TestCase;

final class NonserializedStockTest extends TestCase
{
    // ─── Factory helpers ─────────────────────────────────────────────────────

    private function makeStock(int $qty = 10): NonserializedStock
    {
        return NonserializedStock::initialize(
            new NonserializedStockIdValueObject('01HZ0000000000000000000001'),
            new ProductIdValueObject('01HZ0000000000000000000002'),
            new LocationIdValueObject('01HZ0000000000000000000003'),
            $qty,
        );
    }

    // ─── Initialization ──────────────────────────────────────────────────────

    public function testInitializeCreatesAvailableStock(): void
    {
        $stock = $this->makeStock(10);

        self::assertSame(10, $stock->quantity());
        self::assertSame(NonserializedStockStatus::AVAILABLE, $stock->status());
        self::assertTrue($stock->isAvailable());
    }

    // ─── Add stock ───────────────────────────────────────────────────────────

    public function testAddStockIncreasesQuantity(): void
    {
        $stock = $this->makeStock(5);
        $stock->addStock(10);

        self::assertSame(15, $stock->quantity());
    }

    public function testAddStockOnReservedThrowsException(): void
    {
        $stock = $this->makeStock(5);
        $stock->reserve();

        $this->expectException(StockReservedException::class);
        $stock->addStock(3);
    }

    // ─── Reserve / Release ───────────────────────────────────────────────────

    public function testReserveSetsStatusReserved(): void
    {
        $stock = $this->makeStock();
        $stock->reserve();

        self::assertSame(NonserializedStockStatus::RESERVED, $stock->status());
        self::assertTrue($stock->isReserved());
    }

    public function testReserveAlreadyReservedThrowsException(): void
    {
        $stock = $this->makeStock();
        $stock->reserve();

        $this->expectException(StockReservedException::class);
        $stock->reserve();
    }

    public function testReleaseRestoresAvailable(): void
    {
        $stock = $this->makeStock();
        $stock->reserve();
        $stock->release();

        self::assertSame(NonserializedStockStatus::AVAILABLE, $stock->status());
    }

    public function testReleaseOnAvailableThrowsException(): void
    {
        $stock = $this->makeStock();

        $this->expectException(StockReservedException::class);
        $stock->release();
    }

    // ─── Reduce ──────────────────────────────────────────────────────────────

    public function testReduceDecreasesQuantity(): void
    {
        $stock = $this->makeStock(10);
        $stock->reduce(3);

        self::assertSame(7, $stock->quantity());
    }

    public function testReduceMoreThanAvailableThrowsException(): void
    {
        $stock = $this->makeStock(5);

        $this->expectException(InsufficientStockException::class);
        $stock->reduce(6);
    }

    public function testReduceToZeroSoftDeletes(): void
    {
        $stock = $this->makeStock(3);
        $stock->reduce(3);

        self::assertSame(0, $stock->quantity());
        self::assertTrue($stock->isDeleted());
    }

    // ─── Damage / Decommission / Restore ─────────────────────────────────────

    public function testMarkDamagedSetsDamagedStatus(): void
    {
        $stock = $this->makeStock();
        $stock->markDamaged();

        self::assertSame(NonserializedStockStatus::DAMAGED, $stock->status());
        self::assertTrue($stock->isDamaged());
    }

    public function testDecommissionSetsDecommissionedStatus(): void
    {
        $stock = $this->makeStock();
        $stock->decommission();

        self::assertSame(NonserializedStockStatus::DECOMMISSIONED, $stock->status());
        self::assertTrue($stock->isDecommissioned());
    }

    public function testRestoreFromDamagedSetsAvailable(): void
    {
        $stock = $this->makeStock();
        $stock->markDamaged();
        $stock->restore();

        self::assertSame(NonserializedStockStatus::AVAILABLE, $stock->status());
    }

    public function testRestoreFromAvailableThrowsException(): void
    {
        $stock = $this->makeStock();

        $this->expectException(StockReservedException::class);
        $stock->restore();
    }

    // ─── Soft delete ─────────────────────────────────────────────────────────

    public function testDeleteMarksAsDeleted(): void
    {
        $stock = $this->makeStock();
        self::assertFalse($stock->isDeleted());

        $stock->delete();

        self::assertTrue($stock->isDeleted());
    }
}
