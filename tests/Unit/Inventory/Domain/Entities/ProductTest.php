<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Domain\Entities;

use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    // ─── Factory helpers ─────────────────────────────────────────────────────

    private function makeId(): ProductIdValueObject
    {
        return new ProductIdValueObject('01HZ0000000000000000000001');
    }

    private function makeCode(string $code = 'SUPPLIER-001'): ProductCodeAssignedBySupplierValueObject
    {
        return new ProductCodeAssignedBySupplierValueObject($code);
    }

    private function makeProduct(
        string $name = 'ATM Display',
        string $description = 'Pantalla principal 12"',
        string $trackingType = 'serialized',
    ): Product {
        return Product::add($this->makeId(), $this->makeCode(), new ProductNameValueObject($name), $description, $trackingType);
    }

    // ─── Creation ────────────────────────────────────────────────────────────

    public function testAddCreatesProduct(): void
    {
        $product = $this->makeProduct('ATM Display', 'Pantalla principal');

        self::assertSame('ATM Display', (string) $product->name());
        self::assertSame('Pantalla principal', $product->description());
        self::assertSame('SUPPLIER-001', (string) $product->productCode());
        self::assertSame('serialized', $product->trackingType());
        self::assertTrue($product->isSerialized());
        self::assertFalse($product->isDeleted());
    }

    public function testAddStoresId(): void
    {
        $id      = $this->makeId();
        $product = Product::add($id, $this->makeCode(), new ProductNameValueObject('Name'), 'Desc', 'serialized');

        self::assertSame($id, $product->id());
    }

    public function testNonSerializedProduct(): void
    {
        $product = $this->makeProduct(trackingType: 'non_serialized');

        self::assertSame('non_serialized', $product->trackingType());
        self::assertFalse($product->isSerialized());
    }

    public function testInvalidTrackingTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->makeProduct(trackingType: 'invalid');
    }

    // ─── Update ──────────────────────────────────────────────────────────────

    public function testUpdateChangesNameAndDescription(): void
    {
        $product = $this->makeProduct();
        $product->update(new ProductNameValueObject('New Name'), 'New Desc');

        self::assertSame('New Name', (string) $product->name());
        self::assertSame('New Desc', $product->description());
    }

    // ─── Soft delete ─────────────────────────────────────────────────────────

    public function testDeleteMarksProductAsDeleted(): void
    {
        $product = $this->makeProduct();
        self::assertFalse($product->isDeleted());

        $product->delete();

        self::assertTrue($product->isDeleted());
    }
}
