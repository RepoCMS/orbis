<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\ImportProducts\ImportProductsCommand;
use App\Inventory\Application\Command\ImportProducts\ImportProductsHandler;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Events\ProductCreated;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Infrastructure\Import\ProductCsvFileParser;
use App\Inventory\Infrastructure\Import\ProductRowValidator;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportProductsHandlerTest extends TestCase
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
        ?IdGeneratorInterface $idGenerator = null,
        ?DomainEventPublisherInterface $eventPublisher = null,
    ): ImportProductsHandler {
        return new ImportProductsHandler(
            new ProductCsvFileParser(new CsvFileParser()),
            new ProductRowValidator(),
            $productRepo ?? $this->createMock(ProductRepositoryInterface::class),
            $idGenerator ?? $this->createMock(IdGeneratorInterface::class),
            $eventPublisher ?? $this->createMock(DomainEventPublisherInterface::class),
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

    public function testImportsProductsSuccessfully(): void
    {
        $csv = "code,name,description,tracking_type\nPROD001,Producto Uno,Desc 1,serialized\nPROD002,Producto Dos,Desc 2,non_serialized";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn(null);
        $productRepo->expects($this->exactly(2))->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->exactly(2))->method('publish')
            ->with($this->isInstanceOf(ProductCreated::class));

        $handler = $this->makeHandler($productRepo, $idGenerator, $eventPublisher);
        $result = ($handler)(new ImportProductsCommand(filePath: $path));

        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $csv = "code,name,description,tracking_type\nPROD001,Producto Uno,Desc 1,serialized\nPROD002,Producto Dos,Desc 2,non_serialized";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($productRepo);
        $result = ($handler)(new ImportProductsCommand(filePath: $path, dryRun: true));

        self::assertSame(2, $result->imported);
    }

    public function testSkipsDuplicateCode(): void
    {
        $csv = "code,name,description,tracking_type\nPROD001,Duplicado,Desc 1,serialized";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->createProduct());
        $productRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($productRepo);
        $result = ($handler)(new ImportProductsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(1, $result->skipped);
    }

    public function testReturnsErrorsOnInvalidRows(): void
    {
        $csv = "code,name,description,tracking_type\n,invalid,,serialized\nTOOLONG_ABCDEFGHIJKLMNOPQRSTUVWXYZ_1234567890_ABCDEFGHIJKLMNOPQRSTUVWXYZ,Name,,serialized\nPROD003,Name,,invalid_type";
        $path = $this->createTempCsv($csv);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($productRepo);
        $result = ($handler)(new ImportProductsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(3, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsEmptyOnEmptyFile(): void
    {
        $path = $this->createTempCsv("code,name,description,tracking_type\n");

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportProductsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsErrorOnMissingHeaders(): void
    {
        $csv = "unknown,fields\nvalue1,value2";
        $path = $this->createTempCsv($csv);

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportProductsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }
}
