<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\UpdatePurchaseOrder\UpdatePurchaseOrderCommand;
use App\Inventory\Application\Command\UpdatePurchaseOrder\UpdatePurchaseOrderHandler;
use App\Inventory\Application\Exception\PurchaseOrderNotFoundException;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use PHPUnit\Framework\TestCase;

final class UpdatePurchaseOrderHandlerTest extends TestCase
{
    private const PO_ID   = '01HZ0000000000000000000001';
    private const REF     = 'PO-2026-001';

    public function testUpdatesPurchaseOrder(): void
    {
        $po = PurchaseOrder::create(
            id: PurchaseOrderIdValueObject::fromString(self::PO_ID),
            reference: self::REF,
            notes: null,
            createdBy: new UserIdValueObject('01HZ0000000000000000000002'),
        );

        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->method('findById')->willReturn($po);
        $repo->expects(self::once())->method('save')->with($po);

        $handler = new UpdatePurchaseOrderHandler($repo);
        ($handler)(new UpdatePurchaseOrderCommand(
            purchaseOrderId: self::PO_ID,
            reference: 'PO-2026-002',
            notes: 'Notas actualizadas',
        ));

        self::assertSame('PO-2026-002', $po->reference());
    }

    public function testThrowsWhenPurchaseOrderNotFound(): void
    {
        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdatePurchaseOrderHandler($repo);

        $this->expectException(PurchaseOrderNotFoundException::class);
        ($handler)(new UpdatePurchaseOrderCommand(
            purchaseOrderId: '01HZ0000000000000000000099',
            reference: self::REF,
            notes: null,
        ));
    }
}
