<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Support\Application\Query\ListQuickTemplatesByModel\ListQuickTemplatesByModelHandler;
use App\Support\Application\Query\ListQuickTemplatesByModel\ListQuickTemplatesByModelQuery;
use App\Support\Application\Query\Response\QuickTemplateItemResponse;
use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Entities\QuickTemplateItem;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use App\Support\Domain\ValueObjects\QuickTemplateItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListQuickTemplatesByModelHandlerTest extends TestCase
{
    private const MODEL_ID     = 'model-1';
    private const CREATED_BY   = '01HZ0000000000000000000001';
    private const PRODUCT_ID   = '01HZ0000000000000000000002';

    public function testReturnsActiveTemplatesByModel(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString('01HZ000000000000000000000A'),
            modelId: self::MODEL_ID,
            name: 'Plantilla Test',
            createdBy: self::CREATED_BY,
        );

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findActiveByModelId')->willReturn([$template]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);

        $handler = new ListQuickTemplatesByModelHandler(
            $templateRepo,
            $productRepo,
            $serializedRepo,
            $nonserializedRepo,
        );

        $result = ($handler)(new ListQuickTemplatesByModelQuery(
            modelId: self::MODEL_ID,
            onlyActive: true,
        ));

        self::assertCount(1, $result);
        self::assertSame('Plantilla Test', $result[0]->name);
    }

    public function testReturnsAllTemplatesByModel(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString('01HZ000000000000000000000A'),
            modelId: self::MODEL_ID,
            name: 'Plantilla Test',
            createdBy: self::CREATED_BY,
        );

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findByModelId')->willReturn([$template]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);

        $handler = new ListQuickTemplatesByModelHandler(
            $templateRepo,
            $productRepo,
            $serializedRepo,
            $nonserializedRepo,
        );

        $result = ($handler)(new ListQuickTemplatesByModelQuery(
            modelId: self::MODEL_ID,
            onlyActive: false,
        ));

        self::assertCount(1, $result);
        self::assertSame('Plantilla Test', $result[0]->name);
    }

    public function testReturnsEmptyArrayWhenNoTemplates(): void
    {
        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findByModelId')->willReturn([]);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $nonserializedRepo = $this->createMock(NonserializedStockRepositoryInterface::class);

        $handler = new ListQuickTemplatesByModelHandler(
            $templateRepo,
            $productRepo,
            $serializedRepo,
            $nonserializedRepo,
        );

        $result = ($handler)(new ListQuickTemplatesByModelQuery(
            modelId: self::MODEL_ID,
        ));

        self::assertCount(0, $result);
    }
}
