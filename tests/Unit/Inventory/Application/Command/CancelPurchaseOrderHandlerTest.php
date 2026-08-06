<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\CancelPurchaseOrder\CancelPurchaseOrderCommand;
use App\Inventory\Application\Command\CancelPurchaseOrder\CancelPurchaseOrderHandler;
use App\Inventory\Application\Exception\PurchaseOrderNotFoundException;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use PHPUnit\Framework\TestCase;

final class CancelPurchaseOrderHandlerTest extends TestCase
{
    private const PO_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testCancelsPurchaseOrder(): void
    {
        $po = PurchaseOrder::create(
            id: new PurchaseOrderIdValueObject(self::PO_ID),
            reference: 'PO-2026-001',
            notes: 'Test',
            createdBy: new UserIdValueObject('01JARZ3NDEKTSV4RRFFQ69G5FX'),
        );
        self::assertTrue($po->isOpen());

        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->method('findById')->willReturn($po);
        $repo->expects($this->once())->method('save');

        $handler = new CancelPurchaseOrderHandler($repo);
        ($handler)(new CancelPurchaseOrderCommand(id: self::PO_ID));

        self::assertTrue($po->isCancelled());
    }

    public function testThrowsWhenPurchaseOrderNotFound(): void
    {
        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new CancelPurchaseOrderHandler($repo);

        $this->expectException(PurchaseOrderNotFoundException::class);

        ($handler)(new CancelPurchaseOrderCommand(id: '01HZ0000000000000000000099'));
    }
}
