<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\AddPurchaseOrderItem\AddPurchaseOrderItemCommand;
use App\Inventory\Application\Command\AddPurchaseOrderItem\AddPurchaseOrderItemHandler;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Exception\PurchaseOrderNotFoundException;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class AddPurchaseOrderItemHandlerTest extends TestCase
{
    private const PO_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const PRODUCT_ID = '01JARZ3NDEKTSV4RRFFQ69G5FW';

    public function testAddsItem(): void
    {
        $po = PurchaseOrder::create(
            id: new PurchaseOrderIdValueObject(self::PO_ID),
            reference: 'PO-2026-001',
            notes: null,
            createdBy: new UserIdValueObject('01JARZ3NDEKTSV4RRFFQ69G5FX'),
        );

        $product = Product::add(
            id: new ProductIdValueObject(self::PRODUCT_ID),
            productCode: new ProductCodeAssignedBySupplierValueObject('REP-001'),
            name: new ProductNameValueObject('Repuesto'),
            description: 'Desc',
            trackingType: ProductTrackingType::NON_SERIALIZED,
        );

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findById')->willReturn($po);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $itemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $itemRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01JARZ3NDEKTSV4RRFFQ69G5FZ');

        $handler = new AddPurchaseOrderItemHandler($poRepo, $itemRepo, $productRepo, $idGenerator);
        ($handler)(new AddPurchaseOrderItemCommand(
            purchaseOrderId: self::PO_ID,
            productId: self::PRODUCT_ID,
            quantityOrdered: 10,
        ));
    }

    public function testThrowsWhenPurchaseOrderNotFound(): void
    {
        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findById')->willReturn(null);

        $handler = new AddPurchaseOrderItemHandler(
            $poRepo,
            $this->createMock(PurchaseOrderItemRepositoryInterface::class),
            $this->createMock(ProductRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
        );

        $this->expectException(PurchaseOrderNotFoundException::class);

        ($handler)(new AddPurchaseOrderItemCommand(
            purchaseOrderId: '01HZ0000000000000000000099',
            productId: self::PRODUCT_ID,
            quantityOrdered: 5,
        ));
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $po = PurchaseOrder::create(
            id: new PurchaseOrderIdValueObject(self::PO_ID),
            reference: 'PO-2026-001',
            notes: null,
            createdBy: new UserIdValueObject('01JARZ3NDEKTSV4RRFFQ69G5FX'),
        );

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findById')->willReturn($po);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn(null);

        $handler = new AddPurchaseOrderItemHandler(
            $poRepo,
            $this->createMock(PurchaseOrderItemRepositoryInterface::class),
            $productRepo,
            $this->createMock(IdGeneratorInterface::class),
        );

        $this->expectException(ProductNotFoundException::class);

        ($handler)(new AddPurchaseOrderItemCommand(
            purchaseOrderId: self::PO_ID,
            productId: '01HZ0000000000000000000099',
            quantityOrdered: 5,
        ));
    }
}
