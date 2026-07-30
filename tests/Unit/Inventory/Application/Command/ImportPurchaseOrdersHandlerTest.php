<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\ImportPurchaseOrders\ImportPurchaseOrdersCommand;
use App\Inventory\Application\Command\ImportPurchaseOrders\ImportPurchaseOrdersHandler;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Events\PurchaseOrderCreated;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Infrastructure\Import\PurchaseOrderCsvFileParser;
use App\Inventory\Infrastructure\Import\PurchaseOrderRowValidator;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportPurchaseOrdersHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const USER_ID      = '01ARZ3NDEKTSV4RRFFQ69G5FAX';
    private const PRODUCT_ID   = '01ARZ3NDEKTSV4RRFFQ69G5FAY';

    private function createTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_');
        file_put_contents($path, $content);
        return $path;
    }

    private function makeProduct(): Product
    {
        return Product::add(
            id: new ProductIdValueObject(self::PRODUCT_ID),
            productCode: new ProductCodeAssignedBySupplierValueObject('PROD001'),
            name: new ProductNameValueObject('Test Product'),
            description: 'Test',
            trackingType: ProductTrackingType::SERIALIZED,
        );
    }

    private function makeHandler(
        ?PurchaseOrderRepositoryInterface $poRepo = null,
        ?PurchaseOrderItemRepositoryInterface $poItemRepo = null,
        ?ProductRepositoryInterface $productRepo = null,
        ?IdGeneratorInterface $idGenerator = null,
        ?DomainEventPublisherInterface $eventPublisher = null,
    ): ImportPurchaseOrdersHandler {
        return new ImportPurchaseOrdersHandler(
            new PurchaseOrderCsvFileParser(new CsvFileParser()),
            new PurchaseOrderRowValidator(),
            $poRepo ?? $this->createMock(PurchaseOrderRepositoryInterface::class),
            $poItemRepo ?? $this->createMock(PurchaseOrderItemRepositoryInterface::class),
            $productRepo ?? $this->createMock(ProductRepositoryInterface::class),
            $idGenerator ?? $this->createMock(IdGeneratorInterface::class),
            $eventPublisher ?? $this->createMock(DomainEventPublisherInterface::class),
        );
    }

    public function testImportsPurchaseOrdersSuccessfully(): void
    {
        $csv = "reference,notes,items\nPO-001,Nota pedido 1,PROD001:5\nPO-002,,PROD001:10";
        $path = $this->createTempCsv($csv);

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findByReference')->willReturn(null);
        $poRepo->expects($this->exactly(2))->method('save');

        $poItemRepo = $this->createMock(PurchaseOrderItemRepositoryInterface::class);
        $poItemRepo->expects($this->exactly(2))->method('save');

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->makeProduct());

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->exactly(2))->method('publish')
            ->with($this->isInstanceOf(PurchaseOrderCreated::class));

        $handler = $this->makeHandler($poRepo, $poItemRepo, $productRepo, $idGenerator, $eventPublisher);
        $result = ($handler)(new ImportPurchaseOrdersCommand(filePath: $path, createdBy: self::USER_ID));

        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $csv = "reference,notes,items\nPO-001,Nota,PROD001:3";
        $path = $this->createTempCsv($csv);

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findByReference')->willReturn(null);
        $poRepo->expects($this->never())->method('save');

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn($this->makeProduct());

        $handler = $this->makeHandler(poRepo: $poRepo, productRepo: $productRepo);
        $result = ($handler)(new ImportPurchaseOrdersCommand(filePath: $path, createdBy: self::USER_ID, dryRun: true));

        self::assertSame(1, $result->imported);
    }

    public function testSkipsDuplicateReference(): void
    {
        $csv = "reference,notes,items\nPO-001,Duplicado,PROD001:5";
        $path = $this->createTempCsv($csv);

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findByReference')->willReturn(
            \App\Inventory\Domain\Entities\PurchaseOrder::create(
                id: new \App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject(self::GENERATED_ID),
                reference: 'PO-001',
                notes: 'Existing',
                createdBy: new UserIdValueObject(self::USER_ID),
            ),
        );
        $poRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler(poRepo: $poRepo);
        $result = ($handler)(new ImportPurchaseOrdersCommand(filePath: $path, createdBy: self::USER_ID));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(1, $result->skipped);
    }

    public function testReturnsErrorsOnInvalidRows(): void
    {
        $csv = "reference,notes,items\n,Nota,\n";
        $path = $this->createTempCsv($csv);

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler(poRepo: $poRepo);
        $result = ($handler)(new ImportPurchaseOrdersCommand(filePath: $path, createdBy: self::USER_ID));

        self::assertSame(0, $result->imported);
        self::assertSame(1, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsErrorWhenProductNotFound(): void
    {
        $csv = "reference,notes,items\nPO-001,Nota,UNKNOWN:2";
        $path = $this->createTempCsv($csv);

        $poRepo = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $poRepo->method('findByReference')->willReturn(null);
        $poRepo->expects($this->never())->method('save');

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByCode')->willReturn(null);

        $handler = $this->makeHandler(poRepo: $poRepo, productRepo: $productRepo);
        $result = ($handler)(new ImportPurchaseOrdersCommand(filePath: $path, createdBy: self::USER_ID));

        self::assertSame(0, $result->imported);
        self::assertSame(1, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsEmptyOnEmptyFile(): void
    {
        $path = $this->createTempCsv("reference,notes,items\n");

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportPurchaseOrdersCommand(filePath: $path, createdBy: self::USER_ID));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsErrorOnMissingHeaders(): void
    {
        $csv = "unknown,fields\nvalue1,value2";
        $path = $this->createTempCsv($csv);

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportPurchaseOrdersCommand(filePath: $path, createdBy: self::USER_ID));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }
}
