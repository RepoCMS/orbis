<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\FindProductsByPurchaseOrder\FindProductsByPurchaseOrderHandler;
use App\Inventory\Application\Query\FindProductsByPurchaseOrder\FindProductsByPurchaseOrderQuery;
use App\Inventory\Application\Query\Response\ProductResponse;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Entities\PurchaseOrderItem;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindProductsByPurchaseOrderHandlerTest extends TestCase
{
    private const PURCHASE_ORDER_ID = '01HZ0000000000000000000010';
    private const PO_ITEM_ID_1     = '01HZ0000000000000000000020';
    private const PO_ITEM_ID_2     = '01HZ0000000000000000000021';
    private const PRODUCT_ID_1     = '01HZ0000000000000000000001';
    private const PRODUCT_ID_2     = '01HZ0000000000000000000003';

    private function makeProduct1(): Product
    {
        return Product::add(
            new ProductIdValueObject(self::PRODUCT_ID_1),
            new ProductCodeAssignedBySupplierValueObject('ATM-001'),
            new ProductNameValueObject('ATM Display'),
            'Pantalla para cajero automático',
            ProductTrackingType::SERIALIZED,
        );
    }

    private function makeProduct2(): Product
    {
        return Product::add(
            new ProductIdValueObject(self::PRODUCT_ID_2),
            new ProductCodeAssignedBySupplierValueObject('TEC-002'),
            new ProductNameValueObject('Teclado numérico'),
            'Teclado para cajero automático',
            ProductTrackingType::NON_SERIALIZED,
        );
    }

    /** @return PurchaseOrderItem[] */
    private function makePoItems(): array
    {
        return [
            PurchaseOrderItem::create(
                new PurchaseOrderItemIdValueObject(self::PO_ITEM_ID_1),
                new PurchaseOrderIdValueObject(self::PURCHASE_ORDER_ID),
                new ProductIdValueObject(self::PRODUCT_ID_1),
                5,
            ),
            PurchaseOrderItem::create(
                new PurchaseOrderItemIdValueObject(self::PO_ITEM_ID_2),
                new PurchaseOrderIdValueObject(self::PURCHASE_ORDER_ID),
                new ProductIdValueObject(self::PRODUCT_ID_2),
                3,
            ),
        ];
    }

    private function makeHandler(
        array $poItems = [],
        array $productMap = [],
    ): FindProductsByPurchaseOrderHandler {
        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poItemRepo->method('findByPurchaseOrder')->willReturn($poItems);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturnCallback(
            fn (ProductIdValueObject $id) => $productMap[(string) $id] ?? null,
        );

        return new FindProductsByPurchaseOrderHandler($poItemRepo, $productRepo);
    }

    public function testReturnsProductResponsesWhenFound(): void
    {
        $product1 = $this->makeProduct1();
        $product2 = $this->makeProduct2();

        $handler = $this->makeHandler(
            $this->makePoItems(),
            [
                self::PRODUCT_ID_1 => $product1,
                self::PRODUCT_ID_2 => $product2,
            ],
        );

        $query    = new FindProductsByPurchaseOrderQuery(self::PURCHASE_ORDER_ID);
        $response = ($handler)($query);

        self::assertCount(2, $response);
        self::assertInstanceOf(ProductResponse::class, $response[0]);
        self::assertSame(self::PRODUCT_ID_1, $response[0]->id);
        self::assertSame('ATM-001', $response[0]->code);
        self::assertSame('ATM Display', $response[0]->name);
        self::assertSame(ProductTrackingType::SERIALIZED->value, $response[0]->trackingType);
        self::assertSame(self::PRODUCT_ID_2, $response[1]->id);
        self::assertSame('TEC-002', $response[1]->code);
        self::assertSame('Teclado numérico', $response[1]->name);
        self::assertSame(ProductTrackingType::NON_SERIALIZED->value, $response[1]->trackingType);
    }

    public function testReturnsEmptyArrayWhenNoPoItems(): void
    {
        $handler = $this->makeHandler([]);

        $query    = new FindProductsByPurchaseOrderQuery(self::PURCHASE_ORDER_ID);
        $response = ($handler)($query);

        self::assertCount(0, $response);
    }

    public function testSkipsProductsNotFound(): void
    {
        $product1 = $this->makeProduct1();

        $handler = $this->makeHandler(
            $this->makePoItems(),
            [
                self::PRODUCT_ID_1 => $product1,
            ],
        );

        $query    = new FindProductsByPurchaseOrderQuery(self::PURCHASE_ORDER_ID);
        $response = ($handler)($query);

        self::assertCount(1, $response);
        self::assertSame(self::PRODUCT_ID_1, $response[0]->id);
    }

    public function testReturnsMultipleCopiesWhenSameProductInDifferentItems(): void
    {
        $product1 = $this->makeProduct1();

        $poItems = [
            PurchaseOrderItem::create(
                new PurchaseOrderItemIdValueObject(self::PO_ITEM_ID_1),
                new PurchaseOrderIdValueObject(self::PURCHASE_ORDER_ID),
                new ProductIdValueObject(self::PRODUCT_ID_1),
                5,
            ),
            PurchaseOrderItem::create(
                new PurchaseOrderItemIdValueObject(self::PO_ITEM_ID_2),
                new PurchaseOrderIdValueObject(self::PURCHASE_ORDER_ID),
                new ProductIdValueObject(self::PRODUCT_ID_1),
                3,
            ),
        ];

        $handler = $this->makeHandler(
            $poItems,
            [self::PRODUCT_ID_1 => $product1],
        );

        $query    = new FindProductsByPurchaseOrderQuery(self::PURCHASE_ORDER_ID);
        $response = ($handler)($query);

        self::assertCount(2, $response);
        self::assertSame(self::PRODUCT_ID_1, $response[0]->id);
        self::assertSame(self::PRODUCT_ID_1, $response[1]->id);
    }
}
