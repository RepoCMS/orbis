<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\CreatePurchaseOrderFromSuggestion\CreatePurchaseOrderFromSuggestionCommand;
use App\Inventory\Application\Command\CreatePurchaseOrderFromSuggestion\CreatePurchaseOrderFromSuggestionHandler;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Exception\PurchaseOrderReferenceAlreadyExistsException;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreatePurchaseOrderFromSuggestionHandlerTest extends TestCase
{
    private const PO_REF       = 'PO-2026-001';
    private const CREATED_BY   = '01HZ0000000000000000000001';
    private const PRODUCT_ID   = '01HZ0000000000000000000002';
    private const LOCATION_ID  = '01HZ0000000000000000000003';

    public function testCreatesPurchaseOrderFromSuggestion(): void
    {
        $product = Product::add(
            id: ProductIdValueObject::fromString(self::PRODUCT_ID),
            productCode: ProductCodeAssignedBySupplierValueObject::fromString('PRD-001'),
            name: new ProductNameValueObject('Producto Test'),
            description: 'Test',
            trackingType: ProductTrackingType::NON_SERIALIZED,
        );

        $location = Location::create(
            id: LocationIdValueObject::fromString(self::LOCATION_ID),
            name: new LocationNameValueObject('Almacén Central'),
            city: 'Ciudad',
            address: 'Dirección',
            type: LocationType::WAREHOUSE,
        );

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findByReference')->willReturn(null);
        $poRepo->expects(self::once())->method('save');

        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poItemRepo->expects(self::once())->method('save');

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($location);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects(self::once())->method('publish');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01HZ000000000000000000000A');

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $handler = new CreatePurchaseOrderFromSuggestionHandler(
            $poRepo,
            $poItemRepo,
            $productRepo,
            $locationRepo,
            $eventPublisher,
            $idGenerator,
            $idempotency,
        );

        ($handler)(new CreatePurchaseOrderFromSuggestionCommand(
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            suggestedQuantity: 10,
            reference: self::PO_REF,
            notes: null,
            createdBy: self::CREATED_BY,
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

        $handler = new CreatePurchaseOrderFromSuggestionHandler(
            $poRepo,
            $this->createMock(PurchaseOrderItemRepositoryInterface::class),
            $this->createMock(ProductRepositoryInterface::class),
            $this->createMock(LocationRepositoryInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $idempotency,
        );

        $this->expectException(PurchaseOrderReferenceAlreadyExistsException::class);
        ($handler)(new CreatePurchaseOrderFromSuggestionCommand(
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            suggestedQuantity: 5,
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

        $handler = new CreatePurchaseOrderFromSuggestionHandler(
            $poRepo,
            $this->createMock(PurchaseOrderItemRepositoryInterface::class),
            $productRepo,
            $this->createMock(LocationRepositoryInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $idempotency,
        );

        $this->expectException(ProductNotFoundException::class);
        ($handler)(new CreatePurchaseOrderFromSuggestionCommand(
            productId: '01HZ0000000000000000000099',
            locationId: self::LOCATION_ID,
            suggestedQuantity: 5,
            reference: self::PO_REF,
            notes: null,
            createdBy: self::CREATED_BY,
        ));
    }

    public function testThrowsWhenLocationNotFound(): void
    {
        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findByReference')->willReturn(null);

        $product = Product::add(
            id: ProductIdValueObject::fromString(self::PRODUCT_ID),
            productCode: ProductCodeAssignedBySupplierValueObject::fromString('PRD-001'),
            name: new ProductNameValueObject('Test'),
            description: 'Test',
            trackingType: ProductTrackingType::NON_SERIALIZED,
        );

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn(null);

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $handler = new CreatePurchaseOrderFromSuggestionHandler(
            $poRepo,
            $this->createMock(PurchaseOrderItemRepositoryInterface::class),
            $productRepo,
            $locationRepo,
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $idempotency,
        );

        $this->expectException(LocationNotFoundException::class);
        ($handler)(new CreatePurchaseOrderFromSuggestionCommand(
            productId: self::PRODUCT_ID,
            locationId: '01HZ0000000000000000000099',
            suggestedQuantity: 5,
            reference: self::PO_REF,
            notes: null,
            createdBy: self::CREATED_BY,
        ));
    }
}
