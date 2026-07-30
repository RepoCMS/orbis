<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\RecordTransformation\RecordTransformationCommand;
use App\Inventory\Application\Command\RecordTransformation\RecordTransformationHandler;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Entities\Transformation;
use App\Inventory\Domain\Enums\SerializedStockStatus;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\TransformationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RecordTransformationHandlerTest extends TestCase
{
    private const STOCK_ID = '01HZ0000000000000000000001';
    private const LABORATORY_ID = '01HZ0000000000000000000002';
    private const PERFORMED_BY = '01HZ0000000000000000000003';
    private const NEW_PRODUCT_ID = '01HZ0000000000000000000004';
    private const NEW_SERIAL = 'SN-NEW-001';
    private const TRANSFORMATION_ID = '01HZ0000000000000000000005';
    private const PRODUCT_ID = '01HZ0000000000000000000010';
    private const LOCATION_ID = '01HZ0000000000000000000011';
    private const SERIAL_NUMBER = 'SN-ORIGINAL-001';

    private function makeAvailableVandalismStock(): SerializedStock
    {
        return SerializedStock::register(
            id: new SerializedStockIdValueObject(self::STOCK_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject(self::SERIAL_NUMBER),
            locationId: new LocationIdValueObject(self::LOCATION_ID),
            isVandalism: true,
        );
    }

    private function makeAvailableNonVandalismStock(): SerializedStock
    {
        return SerializedStock::register(
            id: new SerializedStockIdValueObject(self::STOCK_ID),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject(self::SERIAL_NUMBER),
            locationId: new LocationIdValueObject(self::LOCATION_ID),
            isVandalism: false,
        );
    }

    public function testRecordsTransformationWithProductAndSerialChange(): void
    {
        $stock = $this->makeAvailableVandalismStock();

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($stock);
        $serializedRepo->expects($this->once())->method('save')->with($stock);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::TRANSFORMATION_ID);

        $savedTransformation = null;
        $transformationRepo = $this->createMock(TransformationRepositoryInterface::class);
        $transformationRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Transformation $t) use (&$savedTransformation): void {
                $savedTransformation = $t;
            });

        $handler = new RecordTransformationHandler(
            serializedStockRepository: $serializedRepo,
            transformationRepository: $transformationRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new RecordTransformationCommand(
            serializedStockId: self::STOCK_ID,
            laboratoryId: self::LABORATORY_ID,
            performedById: self::PERFORMED_BY,
            newProductId: self::NEW_PRODUCT_ID,
            newSerialNumber: self::NEW_SERIAL,
            notes: 'Transformation test',
        ));

        self::assertSame(self::NEW_PRODUCT_ID, (string) $stock->productId());
        self::assertSame(self::NEW_SERIAL, (string) $stock->serialNumber());

        self::assertNotNull($savedTransformation);
        self::assertSame(self::STOCK_ID, (string) $savedTransformation->serializedStockId());
        self::assertSame(self::PRODUCT_ID, (string) $savedTransformation->originalProductId());
        self::assertSame(self::NEW_PRODUCT_ID, (string) $savedTransformation->newProductId());
        self::assertSame(self::SERIAL_NUMBER, (string) $savedTransformation->originalSerialNumber());
        self::assertSame(self::NEW_SERIAL, (string) $savedTransformation->newSerialNumber());
        self::assertTrue($savedTransformation->changedProduct());
        self::assertTrue($savedTransformation->changedSerial());
    }

    public function testRecordsTransformationWithProductOnly(): void
    {
        $stock = $this->makeAvailableVandalismStock();

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($stock);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::TRANSFORMATION_ID);

        $transformationRepo = $this->createMock(TransformationRepositoryInterface::class);

        $handler = new RecordTransformationHandler(
            serializedStockRepository: $serializedRepo,
            transformationRepository: $transformationRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new RecordTransformationCommand(
            serializedStockId: self::STOCK_ID,
            laboratoryId: self::LABORATORY_ID,
            performedById: self::PERFORMED_BY,
            newProductId: self::NEW_PRODUCT_ID,
            newSerialNumber: null,
            notes: null,
        ));

        self::assertSame(self::NEW_PRODUCT_ID, (string) $stock->productId());
        self::assertSame(self::SERIAL_NUMBER, (string) $stock->serialNumber(), 'Serial should remain unchanged');
    }

    public function testThrowsWhenStockNotFound(): void
    {
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn(null);

        $transformationRepo = $this->createMock(TransformationRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new RecordTransformationHandler(
            serializedStockRepository: $serializedRepo,
            transformationRepository: $transformationRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Stock serializado no encontrado');

        ($handler)(new RecordTransformationCommand(
            serializedStockId: self::STOCK_ID,
            laboratoryId: self::LABORATORY_ID,
            performedById: self::PERFORMED_BY,
            newProductId: self::NEW_PRODUCT_ID,
            newSerialNumber: null,
            notes: null,
        ));
    }

    public function testThrowsWhenStockNotAvailable(): void
    {
        $stock = $this->makeAvailableVandalismStock();
        $ref = new \ReflectionClass($stock);
        $statusProp = $ref->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($stock, SerializedStockStatus::RESERVED);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($stock);

        $transformationRepo = $this->createMock(TransformationRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new RecordTransformationHandler(
            serializedStockRepository: $serializedRepo,
            transformationRepository: $transformationRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('no está disponible');

        ($handler)(new RecordTransformationCommand(
            serializedStockId: self::STOCK_ID,
            laboratoryId: self::LABORATORY_ID,
            performedById: self::PERFORMED_BY,
            newProductId: self::NEW_PRODUCT_ID,
            newSerialNumber: null,
            notes: null,
        ));
    }

    public function testThrowsWhenStockNotVandalism(): void
    {
        $stock = $this->makeAvailableNonVandalismStock();

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($stock);

        $transformationRepo = $this->createMock(TransformationRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new RecordTransformationHandler(
            serializedStockRepository: $serializedRepo,
            transformationRepository: $transformationRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Solo stock marcado como vandalismo');

        ($handler)(new RecordTransformationCommand(
            serializedStockId: self::STOCK_ID,
            laboratoryId: self::LABORATORY_ID,
            performedById: self::PERFORMED_BY,
            newProductId: self::NEW_PRODUCT_ID,
            newSerialNumber: null,
            notes: null,
        ));
    }

    public function testThrowsWhenNoChangesProvided(): void
    {
        $stock = $this->makeAvailableVandalismStock();

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($stock);

        $transformationRepo = $this->createMock(TransformationRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new RecordTransformationHandler(
            serializedStockRepository: $serializedRepo,
            transformationRepository: $transformationRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Se debe indicar al menos un cambio');

        ($handler)(new RecordTransformationCommand(
            serializedStockId: self::STOCK_ID,
            laboratoryId: self::LABORATORY_ID,
            performedById: self::PERFORMED_BY,
            newProductId: null,
            newSerialNumber: null,
            notes: null,
        ));
    }
}
