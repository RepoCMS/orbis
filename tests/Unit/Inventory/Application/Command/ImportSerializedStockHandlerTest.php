<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\ImportSerializedStock\ImportSerializedStockCommand;
use App\Inventory\Application\Command\ImportSerializedStock\ImportSerializedStockHandler;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Infrastructure\Import\SerializedStockCsvFileParser;
use App\Inventory\Infrastructure\Import\SerializedStockRowValidator;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportSerializedStockHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function createTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_');
        file_put_contents($path, $content);
        return $path;
    }

    private function makeHandler(
        ?ProductRepositoryInterface $productRepo = null,
        ?LocationRepositoryInterface $locationRepo = null,
        ?SerializedStockRepositoryInterface $serializedRepo = null,
        ?MovementRepositoryInterface $movementRepo = null,
        ?IdGeneratorInterface $idGenerator = null,
    ): ImportSerializedStockHandler {
        return new ImportSerializedStockHandler(
            new SerializedStockCsvFileParser(new CsvFileParser()),
            new SerializedStockRowValidator(),
            $productRepo ?? $this->createMock(ProductRepositoryInterface::class),
            $locationRepo ?? $this->createMock(LocationRepositoryInterface::class),
            $serializedRepo ?? $this->createMock(SerializedStockRepositoryInterface::class),
            $movementRepo ?? $this->createMock(MovementRepositoryInterface::class),
            $idGenerator ?? $this->createMock(IdGeneratorInterface::class),
        );
    }

    private function createProduct(string $code = 'PROD001'): Product
    {
        return Product::add(
            id: new ProductIdValueObject(self::GENERATED_ID),
            productCode: new ProductCodeAssignedBySupplierValueObject($code),
            name: new ProductNameValueObject('Test Product'),
            description: 'Test',
            trackingType: ProductTrackingType::SERIALIZED,
        );
    }

    private function createLocation(): Location
    {
        return Location::create(
            id: new LocationIdValueObject(self::GENERATED_ID),
            name: new LocationNameValueObject('Test Warehouse'),
            city: 'City',
            address: 'Address',
            type: LocationType::WAREHOUSE,
        );
    }

    public function testImportsSerializedStockSuccessfully(): void
    {
        $csv = "product_code,serial_number,location_id\nPROD001,SERIAL001,01ARZ3NDEKTSV4RRFFQ69G5FAV\nPROD002,SERIAL002,01ARZ3NDEKTSV4RRFFQ69G5FAV";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->createProduct());

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($this->createLocation());

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findBySerial')->willReturn(null);
        $serializedRepo->expects($this->exactly(2))->method('save');

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->exactly(2))->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = $this->makeHandler($productRepo, $locationRepo, $serializedRepo, $movementRepo, $idGenerator);
        $result = ($handler)(new ImportSerializedStockCommand(filePath: $path));

        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $csv = "product_code,serial_number,location_id\nPROD001,SERIAL001,01ARZ3NDEKTSV4RRFFQ69G5FAV";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->createProduct());

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($this->createLocation());

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($productRepo, $locationRepo, $serializedRepo);
        $result = ($handler)(new ImportSerializedStockCommand(filePath: $path, dryRun: true));

        self::assertSame(1, $result->imported);
    }

    public function testSkipsDuplicateSerial(): void
    {
        $csv = "product_code,serial_number,location_id\nPROD001,SERIAL001,01ARZ3NDEKTSV4RRFFQ69G5FAV";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->createProduct());

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($this->createLocation());

        $existing = \App\Inventory\Domain\Entities\SerializedStock::register(
            id: new \App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject(self::GENERATED_ID),
            productId: new ProductIdValueObject(self::GENERATED_ID),
            serialNumber: new \App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject('SERIAL001'),
            locationId: new LocationIdValueObject(self::GENERATED_ID),
        );

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findBySerial')->willReturn($existing);
        $serializedRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($productRepo, $locationRepo, $serializedRepo);
        $result = ($handler)(new ImportSerializedStockCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(1, $result->skipped);
    }

    public function testErrorsWhenProductNotFound(): void
    {
        $csv = "product_code,serial_number,location_id\nPROD001,SERIAL001,01ARZ3NDEKTSV4RRFFQ69G5FAV";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn(null);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($productRepo, null, $serializedRepo);
        $result = ($handler)(new ImportSerializedStockCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(1, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testErrorsWhenProductIsNotSerialized(): void
    {
        $product = Product::add(
            id: new ProductIdValueObject(self::GENERATED_ID),
            productCode: new ProductCodeAssignedBySupplierValueObject('PROD001'),
            name: new ProductNameValueObject('Test Product'),
            description: 'Test',
            trackingType: ProductTrackingType::NON_SERIALIZED,
        );

        $csv = "product_code,serial_number,location_id\nPROD001,SERIAL001,01ARZ3NDEKTSV4RRFFQ69G5FAV";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($product);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($productRepo, null, $serializedRepo);
        $result = ($handler)(new ImportSerializedStockCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(1, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsErrorOnMissingHeaders(): void
    {
        $csv = "unknown,fields\nvalue1,value2";
        $path = $this->createTempCsv($csv);

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportSerializedStockCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }
}
