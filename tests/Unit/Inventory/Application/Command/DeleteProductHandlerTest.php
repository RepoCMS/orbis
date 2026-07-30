<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\DeleteProduct\DeleteProductCommand;
use App\Inventory\Application\Command\DeleteProduct\DeleteProductHandler;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use PHPUnit\Framework\TestCase;

final class DeleteProductHandlerTest extends TestCase
{
    private const PRODUCT_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testDeletesProduct(): void
    {
        $product = Product::add(
            id: new ProductIdValueObject(self::PRODUCT_ID),
            productCode: new ProductCodeAssignedBySupplierValueObject('REP-001'),
            name: new ProductNameValueObject('Repuesto'),
            description: 'Descripción',
            trackingType: ProductTrackingType::NON_SERIALIZED,
        );

        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->method('findById')->willReturn($product);
        $repo->expects($this->once())->method('save');

        $handler = new DeleteProductHandler($repo);
        ($handler)(new DeleteProductCommand(id: self::PRODUCT_ID));

        self::assertTrue($product->isDeleted());
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new DeleteProductHandler($repo);

        $this->expectException(ProductNotFoundException::class);

        ($handler)(new DeleteProductCommand(id: '01HZ0000000000000000000099'));
    }
}
