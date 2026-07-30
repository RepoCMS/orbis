<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\ImportNonserializedStock\ImportNonserializedStockCommand;
use App\Inventory\Application\Command\ImportNonserializedStock\ImportNonserializedStockHandler;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Infrastructure\Import\NonserializedStockCsvFileParser;
use App\Inventory\Infrastructure\Import\NonserializedStockRowValidator;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportNonserializedStockHandlerTest extends TestCase
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
        ?NonserializedStockRepositoryInterface $nonserializedRepo = null,
        ?MovementRepositoryInterface $movementRepo = null,
        ?IdGeneratorInterface $idGenerator = null,
    ): ImportNonserializedStockHandler {
        return new ImportNonserializedStockHandler(
            new NonserializedStockCsvFileParser(new CsvFileParser()),
            new NonserializedStockRowValidator(),
            $productRepo ?? $this->createMock(ProductRepositoryInterface::class),
            $locationRepo ?? $this->createMock(LocationRepositoryInterface::class),
            $nonserializedRepo ?? $this->createMock(NonserializedStockRepositoryInterface::class),
            $movementRepo ?? $this->createMock(MovementRepositoryInterface::class),
            $idGenerator ?? $this->createMock(IdGeneratorInterface::class),
        );
    }

    private function createProduct(bool $isSerialized = false, string $code = 'PROD001'): Product
    {
        return Product::add(
            id: new ProductIdValueObject(self::GENERATED_ID),
            productCode: new ProductCodeAssignedBySupplierValueObject($code),
            name: new ProductNameValueObject('Test Product'),
            description: 'Test',
            trackingType: $isSerialized ? ProductTrackingType::SERIALIZED : ProductTrackingType::NON_SERIALIZED,
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

    public function testImportsNonserializedStockSuccessfully(): void
    {
        $csv = "product_code,location_id,quantity\nPROD001,01ARZ3NDEKTSV4RRFFQ69G5FAV,10\nPROD002,01ARZ3NDEKTSV4RRFFQ69G5FAV,5";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->createProduct());

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($this->createLocation());

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn(null);
        $nonserializedRepo->expects($this->exactly(2))->method('save');

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->exactly(2))->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = $this->makeHandler($productRepo, $locationRepo, $nonserializedRepo, $movementRepo, $idGenerator);
        $result = ($handler)(new ImportNonserializedStockCommand(filePath: $path));

        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testAddsToExistingBatch(): void
    {
        $csv = "product_code,location_id,quantity\nPROD001,01ARZ3NDEKTSV4RRFFQ69G5FAV,10";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->createProduct());

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($this->createLocation());

        $existing = NonserializedStock::initialize(
            id: new \App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject(self::GENERATED_ID),
            productId: new ProductIdValueObject(self::GENERATED_ID),
            locationId: new LocationIdValueObject(self::GENERATED_ID),
            initialQuantity: 5,
        );

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->method('findByProductAndLocationAndStatus')->willReturn($existing);
        $nonserializedRepo->expects($this->once())->method('save');

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = $this->makeHandler($productRepo, $locationRepo, $nonserializedRepo, $movementRepo, $idGenerator);
        $result = ($handler)(new ImportNonserializedStockCommand(filePath: $path));

        self::assertSame(1, $result->imported);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $csv = "product_code,location_id,quantity\nPROD001,01ARZ3NDEKTSV4RRFFQ69G5FAV,10";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->createProduct());

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findById')->willReturn($this->createLocation());

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($productRepo, $locationRepo, $nonserializedRepo);
        $result = ($handler)(new ImportNonserializedStockCommand(filePath: $path, dryRun: true));

        self::assertSame(1, $result->imported);
    }

    public function testErrorsWhenProductNotFound(): void
    {
        $csv = "product_code,location_id,quantity\nPROD001,01ARZ3NDEKTSV4RRFFQ69G5FAV,10";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn(null);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($productRepo, $locationRepo, $nonserializedRepo);
        $result = ($handler)(new ImportNonserializedStockCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(1, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testErrorsWhenProductIsSerialized(): void
    {
        $csv = "product_code,location_id,quantity\nPROD001,01ARZ3NDEKTSV4RRFFQ69G5FAV,10";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->createProduct(true));

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);

        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);
        $nonserializedRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($productRepo, $locationRepo, $nonserializedRepo);
        $result = ($handler)(new ImportNonserializedStockCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(1, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsErrorOnMissingHeaders(): void
    {
        $csv = "unknown,fields\nvalue1,value2";
        $path = $this->createTempCsv($csv);

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportNonserializedStockCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }
}
