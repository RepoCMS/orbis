<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\RegisterEmergencyPartUsage\RegisterEmergencyPartUsageCommand;
use App\Inventory\Application\Command\RegisterEmergencyPartUsage\RegisterEmergencyPartUsageHandler;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Entities\SerialHistory;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Enums\SerializedStockStatus;
use App\Inventory\Domain\Events\EmergencyPartUsed;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerialHistoryRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RegisterEmergencyPartUsageHandlerTest extends TestCase
{
    private const PRODUCT_CODE = 'PRD-001';
    private const EMPLOYEE_ID = '01HZ0000000000000000000001';
    private const SERIAL_NUMBER = 'SN-12345';
    private const CHANGED_BY = '01HZ0000000000000000000002';
    private const PRODUCT_ID = '01HZ0000000000000000000003';
    private const STOCK_ID = '01HZ0000000000000000000004';
    private const BACKPACK_ID = '01HZ0000000000000000000005';
    private const SERIAL_HISTORY_ID = '01HZ0000000000000000000006';

    private function createProduct(): Product
    {
        return Product::add(
            id: ProductIdValueObject::fromString(self::PRODUCT_ID),
            productCode: new ProductCodeAssignedBySupplierValueObject(self::PRODUCT_CODE),
            name: new ProductNameValueObject('Producto de prueba'),
            description: 'Descripción',
            trackingType: ProductTrackingType::SERIALIZED,
        );
    }

    private function createSerializedStock(): SerializedStock
    {
        return SerializedStock::register(
            id: SerializedStockIdValueObject::fromString(self::STOCK_ID),
            productId: ProductIdValueObject::fromString(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject(self::SERIAL_NUMBER),
            locationId: LocationIdValueObject::fromString(self::BACKPACK_ID),
        );
    }

    private function createBackpack(): Location
    {
        return Location::create(
            id: LocationIdValueObject::fromString(self::BACKPACK_ID),
            name: new LocationNameValueObject('Mochila Técnico 1'),
            city: 'Ciudad',
            address: 'Dirección',
            type: LocationType::BACKPACK,
        );
    }

    public function testRegistersEmergencyPartUsage(): void
    {
        $product = $this->createProduct();
        $stock = $this->createSerializedStock();
        $backpack = $this->createBackpack();

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')
            ->with(self::callback(fn (ProductCodeAssignedBySupplierValueObject $c) => (string) $c === self::PRODUCT_CODE))
            ->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findByType')->with(LocationType::BACKPACK)->willReturn([$backpack]);

        $stockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $stockRepo->method('findBySerial')->willReturn($stock);
        $stockRepo->expects(self::once())->method('save')->with(self::isInstanceOf(SerializedStock::class));

        $serialHistoryRepo = $this->createMock(SerialHistoryRepositoryInterface::class);
        $serialHistoryRepo->expects(self::once())->method('save')
            ->with(self::isInstanceOf(SerialHistory::class));

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects(self::once())->method('publish')
            ->with(self::isInstanceOf(EmergencyPartUsed::class));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::SERIAL_HISTORY_ID);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects(self::once())->method('begin');
        $txManager->expects(self::once())->method('commit');

        $handler = new RegisterEmergencyPartUsageHandler(
            $productRepo,
            $locationRepo,
            $stockRepo,
            $serialHistoryRepo,
            $eventPublisher,
            $idGenerator,
            $txManager,
        );

        ($handler)(new RegisterEmergencyPartUsageCommand(
            productCode: self::PRODUCT_CODE,
            employeeId: self::EMPLOYEE_ID,
            serialNumber: self::SERIAL_NUMBER,
            changedBy: self::CHANGED_BY,
            notes: 'Uso de emergencia por falla crítica',
        ));

        self::assertSame(
            SerializedStockStatus::SUSPECT,
            $stock->status(),
        );
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn(null);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects(self::once())->method('begin');
        $txManager->expects(self::once())->method('rollback');

        $handler = new RegisterEmergencyPartUsageHandler(
            $productRepo,
            $this->createMock(LocationRepositoryInterface::class),
            $this->createMock(SerializedStockRepositoryInterface::class),
            $this->createMock(SerialHistoryRepositoryInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $txManager,
        );

        $this->expectException(\InvalidArgumentException::class);

        ($handler)(new RegisterEmergencyPartUsageCommand(
            productCode: 'INVALID',
            employeeId: self::EMPLOYEE_ID,
            serialNumber: self::SERIAL_NUMBER,
            changedBy: self::CHANGED_BY,
            notes: null,
        ));
    }

    public function testThrowsWhenNoBackpackFound(): void
    {
        $product = $this->createProduct();

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findByType')->with(LocationType::BACKPACK)->willReturn([]);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects(self::once())->method('begin');
        $txManager->expects(self::once())->method('rollback');

        $handler = new RegisterEmergencyPartUsageHandler(
            $productRepo,
            $locationRepo,
            $this->createMock(SerializedStockRepositoryInterface::class),
            $this->createMock(SerialHistoryRepositoryInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $txManager,
        );

        $this->expectException(\LogicException::class);

        ($handler)(new RegisterEmergencyPartUsageCommand(
            productCode: self::PRODUCT_CODE,
            employeeId: self::EMPLOYEE_ID,
            serialNumber: self::SERIAL_NUMBER,
            changedBy: self::CHANGED_BY,
            notes: null,
        ));
    }

    public function testThrowsWhenSerializedStockNotFound(): void
    {
        $product = $this->createProduct();
        $backpack = $this->createBackpack();

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($product);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findByType')->with(LocationType::BACKPACK)->willReturn([$backpack]);

        $stockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $stockRepo->method('findBySerial')->willReturn(null);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects(self::once())->method('begin');
        $txManager->expects(self::once())->method('rollback');

        $handler = new RegisterEmergencyPartUsageHandler(
            $productRepo,
            $locationRepo,
            $stockRepo,
            $this->createMock(SerialHistoryRepositoryInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $txManager,
        );

        $this->expectException(\InvalidArgumentException::class);

        ($handler)(new RegisterEmergencyPartUsageCommand(
            productCode: self::PRODUCT_CODE,
            employeeId: self::EMPLOYEE_ID,
            serialNumber: 'SN-NONEXISTENT',
            changedBy: self::CHANGED_BY,
            notes: null,
        ));
    }

    public function testRollsBackOnException(): void
    {
        $stockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $stockRepo->method('findBySerial')->willThrowException(new \RuntimeException('DB error'));

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->createProduct());

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findByType')->with(LocationType::BACKPACK)->willReturn([$this->createBackpack()]);

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects(self::once())->method('begin');
        $txManager->expects(self::once())->method('rollback');

        $handler = new RegisterEmergencyPartUsageHandler(
            $productRepo,
            $locationRepo,
            $stockRepo,
            $this->createMock(SerialHistoryRepositoryInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $txManager,
        );

        $this->expectException(\RuntimeException::class);

        ($handler)(new RegisterEmergencyPartUsageCommand(
            productCode: self::PRODUCT_CODE,
            employeeId: self::EMPLOYEE_ID,
            serialNumber: self::SERIAL_NUMBER,
            changedBy: self::CHANGED_BY,
            notes: null,
        ));
    }
}
