<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Query\ListPurchaseOrders\ListPurchaseOrdersHandler;
use App\Inventory\Application\Query\Response\PurchaseOrderResponse;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Enums\PurchaseOrderStatus;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ListPurchaseOrdersHandlerTest extends TestCase
{
    private const PO_ID_1 = '01HZ0000000000000000000001';
    private const PO_ID_2 = '01HZ0000000000000000000002';
    private const PO_ID_3 = '01HZ0000000000000000000003';
    private const PO_ID_4 = '01HZ0000000000000000000004';
    private const USER_ID = '01HZ0000000000000000000010';

    private function makePurchaseOrder(
        string $id,
        string $reference,
        PurchaseOrderStatus $status,
        ?string $notes = null,
    ): PurchaseOrder {
        return new PurchaseOrder(
            new PurchaseOrderIdValueObject($id),
            $reference,
            $status,
            $notes,
            new UserIdValueObject(self::USER_ID),
            new DateTimeImmutable('2025-01-15 10:00:00'),
        );
    }

    public function testReturnsAllPurchaseOrders(): void
    {
        $po1 = $this->makePurchaseOrder(self::PO_ID_1, 'PO-001', PurchaseOrderStatus::OPEN);
        $po2 = $this->makePurchaseOrder(self::PO_ID_2, 'PO-002', PurchaseOrderStatus::COMPLETED);

        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findAll')
            ->willReturn([$po1, $po2]);

        $handler = new ListPurchaseOrdersHandler($repo);
        $result  = ($handler)();

        self::assertCount(2, $result);
        self::assertInstanceOf(PurchaseOrderResponse::class, $result[0]);
        self::assertInstanceOf(PurchaseOrderResponse::class, $result[1]);
    }

    public function testReturnsEmptyArrayWhenNoPurchaseOrders(): void
    {
        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListPurchaseOrdersHandler($repo);
        $result  = ($handler)();

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $po = $this->makePurchaseOrder(
            self::PO_ID_1,
            'PO-001',
            PurchaseOrderStatus::OPEN,
            'Nota de prueba',
        );

        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$po]);

        $handler = new ListPurchaseOrdersHandler($repo);
        $result  = ($handler)();

        self::assertCount(1, $result);
        self::assertSame(self::PO_ID_1, $result[0]->id);
        self::assertSame('PO-001', $result[0]->reference);
        self::assertSame('open', $result[0]->status);
        self::assertSame('Abierta', $result[0]->statusLabel);
        self::assertSame('Nota de prueba', $result[0]->notes);
        self::assertSame(self::USER_ID, $result[0]->createdBy);
        self::assertSame('2025-01-15 10:00:00', $result[0]->createdAt);
    }

    public function testMapsAllPurchaseOrderStatuses(): void
    {
        $open      = $this->makePurchaseOrder(self::PO_ID_1, 'PO-001', PurchaseOrderStatus::OPEN);
        $partial   = $this->makePurchaseOrder(self::PO_ID_2, 'PO-002', PurchaseOrderStatus::PARTIAL);
        $completed = $this->makePurchaseOrder(self::PO_ID_3, 'PO-003', PurchaseOrderStatus::COMPLETED);
        $cancelled = $this->makePurchaseOrder(self::PO_ID_4, 'PO-004', PurchaseOrderStatus::CANCELLED);

        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$open, $partial, $completed, $cancelled]);

        $handler = new ListPurchaseOrdersHandler($repo);
        $result  = ($handler)();

        self::assertCount(4, $result);
        self::assertSame('open', $result[0]->status);
        self::assertSame('Abierta', $result[0]->statusLabel);
        self::assertSame('partial', $result[1]->status);
        self::assertSame('Parcial', $result[1]->statusLabel);
        self::assertSame('completed', $result[2]->status);
        self::assertSame('Completada', $result[2]->statusLabel);
        self::assertSame('cancelled', $result[3]->status);
        self::assertSame('Cancelada', $result[3]->statusLabel);
    }

    public function testResponseWithNullNotes(): void
    {
        $po = $this->makePurchaseOrder(self::PO_ID_1, 'PO-001', PurchaseOrderStatus::OPEN);

        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$po]);

        $handler = new ListPurchaseOrdersHandler($repo);
        $result  = ($handler)();

        self::assertCount(1, $result);
        self::assertNull($result[0]->notes);
    }
}
