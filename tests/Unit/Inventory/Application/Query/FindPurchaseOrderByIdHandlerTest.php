<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\PurchaseOrderNotFoundException;
use App\Inventory\Application\Query\FindPurchaseOrderById\FindPurchaseOrderByIdHandler;
use App\Inventory\Application\Query\Response\PurchaseOrderItemResponse;
use App\Inventory\Application\Query\Response\PurchaseOrderResponse;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Entities\PurchaseOrderItem;
use App\Inventory\Domain\Enums\PurchaseOrderStatus;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindPurchaseOrderByIdHandlerTest extends TestCase
{
    private const PURCHASE_ORDER_ID = '01HZ0000000000000000000010';
    private const CREATED_BY        = '01HZ0000000000000000000040';
    private const PO_ITEM_ID_1     = '01HZ0000000000000000000020';
    private const PO_ITEM_ID_2     = '01HZ0000000000000000000021';
    private const PRODUCT_ID_1     = '01HZ0000000000000000000001';
    private const PRODUCT_ID_2     = '01HZ0000000000000000000003';

    private function makePurchaseOrder(
        PurchaseOrderStatus $status = PurchaseOrderStatus::OPEN,
    ): PurchaseOrder {
        $po = PurchaseOrder::create(
            new PurchaseOrderIdValueObject(self::PURCHASE_ORDER_ID),
            'PO-001',
            'Orden de compra de prueba',
            new UserIdValueObject(self::CREATED_BY),
        );

        if ($status === PurchaseOrderStatus::PARTIAL) {
            $po->markPartial();
        } elseif ($status === PurchaseOrderStatus::COMPLETED) {
            $po->markCompleted();
        } elseif ($status === PurchaseOrderStatus::CANCELLED) {
            $po->cancel();
        }

        return $po;
    }

    /** @return PurchaseOrderItem[] */
    private function makeItems(): array
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
        ?PurchaseOrder $po = null,
        array $items = [],
    ): FindPurchaseOrderByIdHandler {
        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findById')->willReturn($po);

        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poItemRepo->method('findByPurchaseOrder')->willReturn($items);

        return new FindPurchaseOrderByIdHandler($poRepo, $poItemRepo);
    }

    public function testReturnsPurchaseOrderResponseWithItemsWhenFound(): void
    {
        $handler = $this->makeHandler($this->makePurchaseOrder(), $this->makeItems());

        $response = ($handler)(self::PURCHASE_ORDER_ID);

        self::assertArrayHasKey('po', $response);
        self::assertArrayHasKey('items', $response);
        self::assertInstanceOf(PurchaseOrderResponse::class, $response['po']);
        self::assertInstanceOf(PurchaseOrderItemResponse::class, $response['items'][0]);
        self::assertSame(self::PURCHASE_ORDER_ID, $response['po']->id);
        self::assertSame('PO-001', $response['po']->reference);
        self::assertSame(PurchaseOrderStatus::OPEN->value, $response['po']->status);
        self::assertSame('Abierta', $response['po']->statusLabel);
        self::assertSame('Orden de compra de prueba', $response['po']->notes);
        self::assertSame(self::CREATED_BY, $response['po']->createdBy);
        self::assertCount(2, $response['items']);
        self::assertSame(self::PO_ITEM_ID_1, $response['items'][0]->id);
        self::assertSame(self::PRODUCT_ID_1, $response['items'][0]->productId);
        self::assertSame(5, $response['items'][0]->quantityOrdered);
        self::assertSame(0, $response['items'][0]->quantityReceived);
        self::assertSame(5, $response['items'][0]->quantityPending);
        self::assertFalse($response['items'][0]->fullyReceived);
        self::assertSame(self::PO_ITEM_ID_2, $response['items'][1]->id);
        self::assertSame(self::PRODUCT_ID_2, $response['items'][1]->productId);
        self::assertSame(3, $response['items'][1]->quantityOrdered);
    }

    public function testReturnsPurchaseOrderWithPartialStatus(): void
    {
        $handler = $this->makeHandler($this->makePurchaseOrder(PurchaseOrderStatus::PARTIAL), $this->makeItems());

        $response = ($handler)(self::PURCHASE_ORDER_ID);

        self::assertSame(PurchaseOrderStatus::PARTIAL->value, $response['po']->status);
        self::assertSame('Parcial', $response['po']->statusLabel);
    }

    public function testReturnsPurchaseOrderWithoutItems(): void
    {
        $handler = $this->makeHandler($this->makePurchaseOrder(), []);

        $response = ($handler)(self::PURCHASE_ORDER_ID);

        self::assertCount(0, $response['items']);
    }

    public function testThrowsExceptionWhenNotFound(): void
    {
        $handler = $this->makeHandler(null);

        $this->expectException(PurchaseOrderNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Orden de compra con ID "%s" no encontrada.', self::PURCHASE_ORDER_ID));

        ($handler)(self::PURCHASE_ORDER_ID);
    }
}
