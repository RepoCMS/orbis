<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\CreatePurchaseOrder\CreatePurchaseOrderCommand;
use App\Inventory\Application\Command\CreatePurchaseOrder\CreatePurchaseOrderHandler;
use App\Inventory\Application\Exception\PurchaseOrderReferenceAlreadyExistsException;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreatePurchaseOrderHandlerTest extends TestCase
{
    public function testCreatesPurchaseOrder(): void
    {
        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->method('findByReference')->willReturn(null);
        $repo->expects($this->once())->method('save')
            ->with($this->isInstanceOf(PurchaseOrder::class));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01JARZ3NDEKTSV4RRFFQ69G5FV');

        $handler = new CreatePurchaseOrderHandler($repo, $idGenerator);
        ($handler)(new CreatePurchaseOrderCommand(
            reference: 'PO-2026-001',
            notes: 'Compra de repuestos',
            createdBy: '01JARZ3NDEKTSV4RRFFQ69G5FX',
        ));
    }

    public function testThrowsWhenReferenceAlreadyExists(): void
    {
        $existing = PurchaseOrder::create(
            id: new \App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject('01JARZ3NDEKTSV4RRFFQ69G5FV'),
            reference: 'PO-2026-001',
            notes: 'Existente',
            createdBy: new UserIdValueObject('01JARZ3NDEKTSV4RRFFQ69G5FX'),
        );

        $repo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $repo->method('findByReference')->willReturn($existing);

        $handler = new CreatePurchaseOrderHandler(
            $repo,
            $this->createMock(IdGeneratorInterface::class),
        );

        $this->expectException(PurchaseOrderReferenceAlreadyExistsException::class);

        ($handler)(new CreatePurchaseOrderCommand(
            reference: 'PO-2026-001',
            notes: 'Intento duplicado',
            createdBy: '01JARZ3NDEKTSV4RRFFQ69G5FX',
        ));
    }
}
