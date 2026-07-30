<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\AddProduct\AddProductCommand;
use App\Inventory\Application\Command\AddProduct\AddProductHandler;
use App\Inventory\Application\Exception\ProductCodeAlreadyExistsException;
use App\Inventory\Domain\Exceptions\ProductNotFoundException;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Exceptions\ProductIdException;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class AddProductHandlerTest extends TestCase
{
    private const PRODUCT_ID = '01HZ0000000000000000000001';
    private const PRODUCT_CODE = 'ATM-001';
    private const OTHER_CODE = 'ATM-002';

    private function makeExistingProduct(): Product
    {
        return Product::add(
            new ProductIdValueObject('01HZ0000000000000000000010'),
            new ProductCodeAssignedBySupplierValueObject(self::PRODUCT_CODE),
            new ProductNameValueObject('Existing Product'),
            'Existing description',
            ProductTrackingType::SERIALIZED,
        );
    }

    public function testAddsProductSuccessfully(): void
    {
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $productRepo->method('findAll')->willReturn([]);
        $idGenerator->method('generate')->willReturn(self::PRODUCT_ID);

        $savedProduct = null;
        $productRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Product $p) use (&$savedProduct): void {
                $savedProduct = $p;
            });
        $handler = new AddProductHandler($productRepo, $idGenerator);

        ($handler)(new AddProductCommand(
            code: self::PRODUCT_CODE,
            name: 'ATM Display',
            description: 'Repuesto para pantalla de ATM',
            trackingType: ProductTrackingType::SERIALIZED->value,
        ));

        self::assertNotNull($savedProduct);
        self::assertSame(self::PRODUCT_ID, (string) $savedProduct->id());
        self::assertSame(self::PRODUCT_CODE, (string) $savedProduct->productCode());
        self::assertSame('ATM Display', (string) $savedProduct->name());
        self::assertSame('Repuesto para pantalla de ATM', $savedProduct->description());
        self::assertTrue($savedProduct->trackingType() === ProductTrackingType::SERIALIZED);
    }

    public function testAddsNonSerializedProductSuccessfully(): void
    {
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $productRepo->method('findAll')->willReturn([]);
        $idGenerator->method('generate')->willReturn(self::PRODUCT_ID);

        $savedProduct = null;
        $productRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Product $p) use (&$savedProduct): void {
                $savedProduct = $p;
            });

        $handler = new AddProductHandler($productRepo, $idGenerator);
        ($handler)(new AddProductCommand(
            code: self::PRODUCT_CODE,
            name: 'Thermal Paper Roll',
            description: 'Papel térmico para impresora de ATM',
            trackingType: ProductTrackingType::NON_SERIALIZED->value,
        ));

        self::assertNotNull($savedProduct);
        self::assertTrue($savedProduct->trackingType() === ProductTrackingType::NON_SERIALIZED);
    }

    public function testThrowsWhenCodeAlreadyExists(): void
    {
        $existing = $this->makeExistingProduct();

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findAll')->willReturn([$existing]);

        $productRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new AddProductHandler($productRepo, $idGenerator);

        $this->expectException(ProductCodeAlreadyExistsException::class);
        $this->expectExceptionMessage(self::PRODUCT_CODE);

        ($handler)(new AddProductCommand(
            code: self::PRODUCT_CODE,
            name: 'ATM Display',
            description: 'Desc',
            trackingType: ProductTrackingType::SERIALIZED->value,
        ));
    }

    public function testThrowsWhenCodeIsEmpty(): void
    {
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findAll')->willReturn([]);
        $productRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::PRODUCT_ID);

        $handler = new AddProductHandler($productRepo, $idGenerator);

        $this->expectException(ProductIdException::class);
        $this->expectExceptionMessage('no puede estar vacío');

        ($handler)(new AddProductCommand(
            code: '',
            name: 'ATM Display',
            description: 'Desc',
            trackingType: ProductTrackingType::SERIALIZED->value,
        ));
    }

    public function testThrowsWhenNameIsEmpty(): void
    {
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findAll')->willReturn([]);
        $productRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::PRODUCT_ID);

        $handler = new AddProductHandler($productRepo, $idGenerator);

        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('no puede estar vacío');

        ($handler)(new AddProductCommand(
            code: self::PRODUCT_CODE,
            name: '',
            description: 'Desc',
            trackingType: ProductTrackingType::SERIALIZED->value,
        ));
    }
}
