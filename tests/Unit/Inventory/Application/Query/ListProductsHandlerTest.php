<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListProducts\ListProductsHandler;
use App\Inventory\Application\Query\Response\ProductResponse;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use PHPUnit\Framework\TestCase;

final class ListProductsHandlerTest extends TestCase
{
    private const PRODUCT_ID_1 = '01HZ0000000000000000000001';
    private const PRODUCT_ID_2 = '01HZ0000000000000000000002';

    private function makeProduct(string $id, string $code, string $name, ProductTrackingType $type): Product
    {
        return Product::add(
            new ProductIdValueObject($id),
            new ProductCodeAssignedBySupplierValueObject($code),
            new ProductNameValueObject($name),
            'Description for ' . $name,
            $type,
        );
    }

    private function makeHandler(array $products): ListProductsHandler
    {
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->method('findAll')->willReturn($products);

        return new ListProductsHandler($repo);
    }

    public function testReturnsAllProducts(): void
    {
        $product1 = $this->makeProduct(
            self::PRODUCT_ID_1,
            'ATM-001',
            'ATM Display',
            ProductTrackingType::SERIALIZED,
        );
        $product2 = $this->makeProduct(
            self::PRODUCT_ID_2,
            'PAPER-001',
            'Papel Térmico',
            ProductTrackingType::NON_SERIALIZED,
        );

        $handler = $this->makeHandler([$product1, $product2]);
        $result  = ($handler)();

        self::assertCount(2, $result);
        self::assertInstanceOf(ProductResponse::class, $result[0]);
        self::assertInstanceOf(ProductResponse::class, $result[1]);
        self::assertSame('ATM Display', $result[0]->name);
        self::assertSame('serialized', $result[0]->trackingType);
        self::assertSame('Papel Térmico', $result[1]->name);
        self::assertSame('non_serialized', $result[1]->trackingType);
    }

    public function testReturnsEmptyArrayWhenNoProducts(): void
    {
        $handler = $this->makeHandler([]);
        $result  = ($handler)();

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $product = $this->makeProduct(
            self::PRODUCT_ID_1,
            'ATM-001',
            'ATM Display',
            ProductTrackingType::SERIALIZED,
        );

        $handler = $this->makeHandler([$product]);
        $result  = ($handler)();

        self::assertCount(1, $result);
        self::assertSame(self::PRODUCT_ID_1, $result[0]->id);
        self::assertSame('ATM-001', $result[0]->code);
        self::assertSame('ATM Display', $result[0]->name);
        self::assertSame('Description for ATM Display', $result[0]->description);
        self::assertSame('serialized', $result[0]->trackingType);
    }

    public function testMapsMixedTrackingTypes(): void
    {
        $serialized = $this->makeProduct(
            self::PRODUCT_ID_1,
            'SERIAL-001',
            'Serial Item',
            ProductTrackingType::SERIALIZED,
        );
        $nonSerialized = $this->makeProduct(
            self::PRODUCT_ID_2,
            'BULK-001',
            'Bulk Item',
            ProductTrackingType::NON_SERIALIZED,
        );

        $handler = $this->makeHandler([$serialized, $nonSerialized]);
        $result  = ($handler)();

        self::assertCount(2, $result);
        self::assertSame('serialized', $result[0]->trackingType);
        self::assertSame('non_serialized', $result[1]->trackingType);
    }
}
