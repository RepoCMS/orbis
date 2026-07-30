<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Query\FindProductById\FindProductByIdHandler;
use App\Inventory\Application\Query\Response\ProductResponse;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use PHPUnit\Framework\TestCase;

final class FindProductByIdHandlerTest extends TestCase
{
    private const PRODUCT_ID = '01HZ0000000000000000000001';

    private function makeProduct(): Product
    {
        return Product::add(
            new ProductIdValueObject(self::PRODUCT_ID),
            new ProductCodeAssignedBySupplierValueObject('ATM-001'),
            new ProductNameValueObject('ATM Display'),
            'Pantalla para cajero automático',
            ProductTrackingType::SERIALIZED,
        );
    }

    private function makeHandler(?Product $product = null): FindProductByIdHandler
    {
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->method('findById')->willReturn($product);

        return new FindProductByIdHandler($repo);
    }

    public function testReturnsProductResponseWhenFound(): void
    {
        $handler = $this->makeHandler($this->makeProduct());

        $response = ($handler)(self::PRODUCT_ID);

        self::assertInstanceOf(ProductResponse::class, $response);
        self::assertSame(self::PRODUCT_ID, $response->id);
        self::assertSame('ATM-001', $response->code);
        self::assertSame('ATM Display', $response->name);
        self::assertSame('Pantalla para cajero automático', $response->description);
        self::assertSame(ProductTrackingType::SERIALIZED->value, $response->trackingType);
    }

    public function testThrowsExceptionWhenNotFound(): void
    {
        $handler = $this->makeHandler(null);

        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Producto con ID "%s" no encontrado.', self::PRODUCT_ID));

        ($handler)(self::PRODUCT_ID);
    }
}
