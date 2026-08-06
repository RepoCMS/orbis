<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\ApprovePhysicalCount\ApprovePhysicalCountCommand;
use App\Inventory\Application\Command\ApprovePhysicalCount\ApprovePhysicalCountHandler;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\PhysicalCount;
use App\Inventory\Domain\Entities\PhysicalCountItem;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Events\PhysicalCountApproved;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use App\Inventory\Domain\ValueObjects\PhysicalCountItemIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class ApprovePhysicalCountHandlerTest extends TestCase
{
    private const COUNT_ID = '01HZ0000000000000000000001';
    private const APPROVED_BY = '01HZ0000000000000000000002';
    private const MOVEMENT_ID = '01HZ0000000000000000000003';
    private const PRODUCT_ID = '01HZ0000000000000000000004';
    private const PRODUCT_CODE = 'ATM-001';

    private function makePendingApprovalCount(): PhysicalCount
    {
        $count = PhysicalCount::start(
            id: new PhysicalCountIdValueObject(self::COUNT_ID),
            locationId: 'LOC-001',
            countedBy: 'Counter',
            notes: 'Test count',
        );

        $item = PhysicalCountItem::record(
            id: new PhysicalCountItemIdValueObject('01HZ0000000000000000000010'),
            physicalCount: $count,
            productCode: self::PRODUCT_CODE,
            expectedQty: 10,
            countedQty: 8,
            notes: null,
        );

        $count->addItem($item);
        $count->submit();

        return $count;
    }

    private function makeDraftCount(): PhysicalCount
    {
        $count = PhysicalCount::start(
            id: new PhysicalCountIdValueObject('01HZ0000000000000000000099'),
            locationId: 'LOC-001',
            countedBy: 'Counter',
        );

        $item = PhysicalCountItem::record(
            id: new PhysicalCountItemIdValueObject('01HZ0000000000000000000011'),
            physicalCount: $count,
            productCode: self::PRODUCT_CODE,
            expectedQty: 10,
            countedQty: 10,
            notes: null,
        );

        $count->addItem($item);

        return $count;
    }

    private function makeProduct(): Product
    {
        return Product::add(
            new ProductIdValueObject(self::PRODUCT_ID),
            new ProductCodeAssignedBySupplierValueObject(self::PRODUCT_CODE),
            new ProductNameValueObject('ATM Display'),
            'Test product',
            ProductTrackingType::NON_SERIALIZED,
        );
    }

    public function testApprovesCountWithAdjustments(): void
    {
        $count = $this->makePendingApprovalCount();
        $product = $this->makeProduct();

        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->method('findById')->willReturn($count);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($product);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $publishedEvents = [];
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())
            ->method('publish')
            ->willReturnCallback(function (PhysicalCountApproved $e) use (&$publishedEvents): void {
                $publishedEvents[] = $e;
            });

        $handler = new ApprovePhysicalCountHandler(
            physicalCountRepository: $countRepo,
            productRepository: $productRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            eventPublisher: $eventPublisher,
        );

        ($handler)(new ApprovePhysicalCountCommand(
            countId: self::COUNT_ID,
            approvedBy: self::APPROVED_BY,
        ));

        self::assertTrue($count->isApproved());
        self::assertSame(self::APPROVED_BY, $count->approvedBy());
        self::assertNotNull($count->approvedAt());

        self::assertCount(1, $savedMovements);
        $movement = $savedMovements[0];
        self::assertSame(MovementType::ADJUSTMENT, $movement->type());
        self::assertSame(-2, $movement->quantity(), 'Adjustment should be 8-10 = -2');

        self::assertCount(1, $publishedEvents);
        $event = $publishedEvents[0];
        self::assertSame(self::COUNT_ID, $event->countId);
        self::assertSame(self::APPROVED_BY, $event->approvedBy);
        self::assertSame(1, $event->adjustedItems);
    }

    public function testApprovesCountWithoutAdjustments(): void
    {
        $count = PhysicalCount::start(
            id: new PhysicalCountIdValueObject(self::COUNT_ID),
            locationId: 'LOC-001',
            countedBy: 'Counter',
        );

        $item = PhysicalCountItem::record(
            id: new PhysicalCountItemIdValueObject('01HZ0000000000000000000010'),
            physicalCount: $count,
            productCode: self::PRODUCT_CODE,
            expectedQty: 10,
            countedQty: 10,
        );

        $count->addItem($item);
        $count->submit();

        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->method('findById')->willReturn($count);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish');

        $handler = new ApprovePhysicalCountHandler(
            physicalCountRepository: $countRepo,
            productRepository: $productRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            eventPublisher: $eventPublisher,
        );

        ($handler)(new ApprovePhysicalCountCommand(
            countId: self::COUNT_ID,
            approvedBy: self::APPROVED_BY,
        ));

        self::assertTrue($count->isApproved());
    }

    public function testThrowsWhenCountNotFound(): void
    {
        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->method('findById')->willReturn(null);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new ApprovePhysicalCountHandler(
            physicalCountRepository: $countRepo,
            productRepository: $productRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            eventPublisher: $eventPublisher,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El conteo físico no existe');

        ($handler)(new ApprovePhysicalCountCommand(
            countId: self::COUNT_ID,
            approvedBy: self::APPROVED_BY,
        ));
    }

    public function testThrowsWhenCountNotPendingApproval(): void
    {
        $count = $this->makeDraftCount();

        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->method('findById')->willReturn($count);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new ApprovePhysicalCountHandler(
            physicalCountRepository: $countRepo,
            productRepository: $productRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            eventPublisher: $eventPublisher,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Solo se pueden aprobar conteos en estado pendiente de aprobación');

        ($handler)(new ApprovePhysicalCountCommand(
            countId: '01HZ0000000000000000000099',
            approvedBy: self::APPROVED_BY,
        ));
    }
}
