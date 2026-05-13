<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Domain\Entities;

use App\Inventory\Domain\Entities\StockMinimum;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;
use PHPUnit\Framework\TestCase;

final class StockMinimumTest extends TestCase
{
    // ─── Factory helpers ──────────────────────────────────────────────────────

    private function makeId(): StockMinimumIdValueObject
    {
        return new StockMinimumIdValueObject('01HZ0000000000000000000001');
    }

    private function makeProductId(): ProductIdValueObject
    {
        return new ProductIdValueObject('01HZ0000000000000000000002');
    }

    private function makeLocationId(): LocationIdValueObject
    {
        return new LocationIdValueObject('01HZ0000000000000000000003');
    }

    private function makeMinimum(
        int $idealQuantity  = 10,
        int $alertThreshold = 3,
    ): StockMinimum {
        return StockMinimum::define(
            id:             $this->makeId(),
            productId:      $this->makeProductId(),
            locationId:     $this->makeLocationId(),
            idealQuantity:  $idealQuantity,
            alertThreshold: $alertThreshold,
        );
    }

    // ─── Creation ─────────────────────────────────────────────────────────────

    public function testDefineCreatesMinimum(): void
    {
        $minimum = $this->makeMinimum(10, 3);

        self::assertSame('01HZ0000000000000000000001', (string) $minimum->id());
        self::assertSame('01HZ0000000000000000000002', (string) $minimum->productId());
        self::assertSame('01HZ0000000000000000000003', (string) $minimum->locationId());
        self::assertSame(10, $minimum->idealQuantity());
        self::assertSame(3, $minimum->alertThreshold());
    }

    public function testDefineAllowsThresholdEqualToIdeal(): void
    {
        $minimum = $this->makeMinimum(5, 5);

        self::assertSame(5, $minimum->idealQuantity());
        self::assertSame(5, $minimum->alertThreshold());
    }

    public function testDefineAllowsZeroThreshold(): void
    {
        $minimum = $this->makeMinimum(10, 0);

        self::assertSame(0, $minimum->alertThreshold());
    }

    public function testDefineAllowsZeroIdealQuantity(): void
    {
        $minimum = $this->makeMinimum(0, 0);

        self::assertSame(0, $minimum->idealQuantity());
        self::assertSame(0, $minimum->alertThreshold());
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    public function testDefineThrowsWhenIdealQuantityIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cantidad ideal');

        $this->makeMinimum(-1, 0);
    }

    public function testDefineThrowsWhenAlertThresholdIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('umbral de alerta');

        $this->makeMinimum(10, -1);
    }

    public function testDefineThrowsWhenThresholdExceedsIdeal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('umbral de alerta no puede superar');

        $this->makeMinimum(5, 6);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function testUpdateChangesValues(): void
    {
        $minimum = $this->makeMinimum(10, 3);
        $minimum->update(20, 5);

        self::assertSame(20, $minimum->idealQuantity());
        self::assertSame(5, $minimum->alertThreshold());
    }

    public function testUpdateThrowsWhenThresholdExceedsIdeal(): void
    {
        $minimum = $this->makeMinimum(10, 3);

        $this->expectException(\InvalidArgumentException::class);

        $minimum->update(5, 10);
    }

    public function testUpdateThrowsWhenIdealIsNegative(): void
    {
        $minimum = $this->makeMinimum(10, 3);

        $this->expectException(\InvalidArgumentException::class);

        $minimum->update(-1, 0);
    }

    // ─── isBelowThreshold ────────────────────────────────────────────────────

    public function testIsBelowThresholdWhenStockEqualToThreshold(): void
    {
        $minimum = $this->makeMinimum(10, 3);

        // currentStock == alertThreshold → below (triggers alert)
        self::assertTrue($minimum->isBelowThreshold(3));
    }

    public function testIsBelowThresholdWhenStockLessThanThreshold(): void
    {
        $minimum = $this->makeMinimum(10, 3);

        self::assertTrue($minimum->isBelowThreshold(1));
    }

    public function testIsNotBelowThresholdWhenStockAboveThreshold(): void
    {
        $minimum = $this->makeMinimum(10, 3);

        self::assertFalse($minimum->isBelowThreshold(4));
    }

    public function testIsNotBelowThresholdWhenStockEqualsIdeal(): void
    {
        $minimum = $this->makeMinimum(10, 3);

        self::assertFalse($minimum->isBelowThreshold(10));
    }

    public function testIsBelowThresholdWithZeroThreshold(): void
    {
        $minimum = $this->makeMinimum(10, 0);

        // Only triggers when stock is 0
        self::assertTrue($minimum->isBelowThreshold(0));
        self::assertFalse($minimum->isBelowThreshold(1));
    }
}
