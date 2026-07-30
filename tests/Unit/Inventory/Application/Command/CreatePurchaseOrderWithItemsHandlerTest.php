<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\CreatePurchaseOrderWithItems\CreatePurchaseOrderWithItemsCommand;
use App\Inventory\Application\Command\CreatePurchaseOrderWithItems\CreatePurchaseOrderWithItemsHandler;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Exception\PurchaseOrderReferenceAlreadyExistsException;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreatePurchaseOrderWithItemsHandlerTest extends TestCase
{
    private const PO_REF      = 'PO-2026-001';
    private const CREATED_BY  = '01HZ0000000000000000000001';
    private const PRODUCT_ID  = '01HZ0000000000000000000002';

    public function testCreatesPurchaseOrderWithItems(): void
    {
        $product = Product::add(
            id: ProductIdValueObject::fromString(self::PRODUCT_ID),
            productCode: ProductCodeAssignedBySupplierValueObject::fromString('PRD-001'),
            name: new ProductNameValueObject('Producto Test'),
            description: 'Test',
            trackingType: ProductTrackingType::NON_SERIALIZED,
        );

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findByReference')->willReturn(null);
        $poRepo->expects(self::once())->method('save');

        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poItemRepo->expects(self::once())->method('save');

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects(self::once())->method('publish');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01HZ000000000000000000000A');

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $handler = new CreatePurchaseOrderWithItemsHandler(
            $poRepo,
            $poItemRepo,
            $productRepo,
            $eventPublisher,
            $idGenerator,
            $idempotency,
        );

        ($handler)(new CreatePurchaseOrderWithItemsCommand(
            reference: self::PO_REF,
            notes: null,
            createdBy: self::CREATED_BY,
            items: [
                ['productId' => self::PRODUCT_ID, 'quantityOrdered' => 10],
            ],
        ));
    }

    public function testThrowsWhenReferenceDuplicated(): void
    {
        $existing = \App\Inventory\Domain\Entities\PurchaseOrder::create(
            id: \App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject::fromString('01HZ0000000000000000000099'),
            reference: self::PO_REF,
            notes: null,
            createdBy: new UserIdValueObject(self::CREATED_BY),
        );

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findByReference')->willReturn($existing);

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $handler = new CreatePurchaseOrderWithItemsHandler(
            $poRepo,
            $this->createMock(PurchaseOrderItemRepositoryInterface::class),
            $this->createMock(ProductRepositoryInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $idempotency,
        );

        $this->expectException(PurchaseOrderReferenceAlreadyExistsException::class);
        ($handler)(new CreatePurchaseOrderWithItemsCommand(
            reference: self::PO_REF,
            notes: null,
            createdBy: self::CREATED_BY,
        ));
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findByReference')->willReturn(null);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn(null);

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $handler = new CreatePurchaseOrderWithItemsHandler(
            $poRepo,
            $this->createMock(PurchaseOrderItemRepositoryInterface::class),
            $productRepo,
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $idempotency,
        );

        $this->expectException(ProductNotFoundException::class);
        ($handler)(new CreatePurchaseOrderWithItemsCommand(
            reference: self::PO_REF,
            notes: null,
            createdBy: self::CREATED_BY,
            items: [
                ['productId' => '01HZ0000000000000000000099', 'quantityOrdered' => 5],
            ],
        ));
    }
}
