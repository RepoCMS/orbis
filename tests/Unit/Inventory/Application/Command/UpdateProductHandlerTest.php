<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\UpdateProduct\UpdateProductCommand;
use App\Inventory\Application\Command\UpdateProduct\UpdateProductHandler;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateProductHandlerTest extends TestCase
{
    private const PRODUCT_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testUpdatesProduct(): void
    {
        $product = Product::add(
            id: new ProductIdValueObject(self::PRODUCT_ID),
            productCode: new ProductCodeAssignedBySupplierValueObject('REP-001'),
            name: new ProductNameValueObject('Nombre Original'),
            description: 'Descripción original',
            trackingType: ProductTrackingType::NON_SERIALIZED,
        );

        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->method('findById')->willReturn($product);
        $repo->expects($this->once())->method('save');

        $handler = new UpdateProductHandler(
            $repo,
            $this->createMock(SerializedStockRepositoryInterface::class),
            $this->createMock(NonserializedStockRepositoryInterface::class),
        );

        ($handler)(new UpdateProductCommand(
            id: self::PRODUCT_ID,
            name: 'Nombre Actualizado',
            description: 'Descripción actualizada',
        ));

        self::assertSame('Nombre Actualizado', $product->name()->value());
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateProductHandler(
            $repo,
            $this->createMock(SerializedStockRepositoryInterface::class),
            $this->createMock(NonserializedStockRepositoryInterface::class),
        );

        $this->expectException(ProductNotFoundException::class);

        ($handler)(new UpdateProductCommand(
            id: '01HZ0000000000000000000099',
            name: 'Test',
            description: 'Test',
        ));
    }
}
