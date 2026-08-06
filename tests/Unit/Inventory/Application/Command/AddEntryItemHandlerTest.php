<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\AddEntryItem\AddEntryItemCommand;
use App\Inventory\Application\Command\AddEntryItem\AddEntryItemHandler;
use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Entities\EntryItem;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Repositories\EntryItemRepositoryInterface;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class AddEntryItemHandlerTest extends TestCase
{
    private const ENTRY_ID    = '01HZ0000000000000000000001';
    private const PRODUCT_ID  = '01HZ0000000000000000000002';
    private const LOCATION_ID = '01HZ0000000000000000000003';
    private const CREATED_BY  = '01HZ0000000000000000000004';
    private const ITEM_ID     = '01HZ0000000000000000000005';

    private function makeEntry(): Entry
    {
        return Entry::create(
            new EntryIdValueObject(self::ENTRY_ID),
            'ENTRY-001',
            null,
            new \App\Identity\Domain\ValueObjects\UserIdValueObject(self::CREATED_BY),
        );
    }

    private function makeSerializedProduct(): Product
    {
        return Product::add(
            id:              new ProductIdValueObject(self::PRODUCT_ID),
            productCode:     new ProductCodeAssignedBySupplierValueObject('SERIAL-PROD'),
            name:            new ProductNameValueObject('Producto Serializado'),
            description:     '',
            trackingType:    ProductTrackingType::SERIALIZED,
            isTransformable: false,
        );
    }

    private function makeNonSerializedProduct(): Product
    {
        return Product::add(
            id:              new ProductIdValueObject(self::PRODUCT_ID),
            productCode:     new ProductCodeAssignedBySupplierValueObject('NS-PROD'),
            name:            new ProductNameValueObject('Producto No Serializado'),
            description:     '',
            trackingType:    ProductTrackingType::NON_SERIALIZED,
            isTransformable: false,
        );
    }

    public function testAddsNonSerializedItemSuccessfully(): void
    {
        $entry   = $this->makeEntry();
        $product = $this->makeNonSerializedProduct();

        $entryRepo    = $this->createMock(EntryRepositoryInterface::class);
        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $productRepo  = $this->createMock(ProductRepositoryInterface::class);
        $locRepo      = $this->createMock(LocationRepositoryInterface::class);
        $idGen        = $this->createMock(IdGeneratorInterface::class);

        $entryRepo->method('findById')->willReturn($entry);
        $productRepo->method('findById')->willReturn($product);
        $locRepo->method('findById')->willReturn(\App\Inventory\Domain\Entities\Location::create(
            id: new \App\Inventory\Domain\ValueObjects\LocationIdValueObject(self::LOCATION_ID),
            name: new \App\Inventory\Domain\ValueObjects\LocationNameValueObject('Bodega'),
            city: 'Quito',
            address: '',
            type: \App\Inventory\Domain\Enums\LocationType::WAREHOUSE,
        ));
        $idGen->method('generate')->willReturn(self::ITEM_ID);

        $entryItemRepo->expects($this->once())->method('save')->with($this->isInstanceOf(EntryItem::class));

        $handler = new AddEntryItemHandler($entryRepo, $entryItemRepo, $productRepo, $locRepo, $idGen);
        ($handler)(new AddEntryItemCommand(
            entryId:   self::ENTRY_ID,
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: null,
            quantity:  10,
            purchaseOrderItemId: null,
        ));
    }

    public function testThrowsWhenEntryNotFound(): void
    {
        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $locRepo = $this->createMock(LocationRepositoryInterface::class);
        $idGen = $this->createMock(IdGeneratorInterface::class);

        $entryRepo->method('findById')->willReturn(null);
        $entryItemRepo->expects($this->never())->method('save');

        $handler = new AddEntryItemHandler($entryRepo, $entryItemRepo, $productRepo, $locRepo, $idGen);

        $this->expectException(EntryNotFoundException::class);

        ($handler)(new AddEntryItemCommand(
            entryId:   self::ENTRY_ID,
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: null,
            quantity:  1,
            purchaseOrderItemId: null,
        ));
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $entry   = $this->makeEntry();

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $locRepo = $this->createMock(LocationRepositoryInterface::class);
        $idGen = $this->createMock(IdGeneratorInterface::class);

        $entryRepo->method('findById')->willReturn($entry);
        $productRepo->method('findById')->willReturn(null);

        $handler = new AddEntryItemHandler($entryRepo, $entryItemRepo, $productRepo, $locRepo, $idGen);

        $this->expectException(ProductNotFoundException::class);

        ($handler)(new AddEntryItemCommand(
            entryId:   self::ENTRY_ID,
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: null,
            quantity:  1,
            purchaseOrderItemId: null,
        ));
    }

    public function testThrowsWhenSerializedProductWithoutSerialNumbers(): void
    {
        $entry   = $this->makeEntry();
        $product = $this->makeSerializedProduct();

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryItemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $locRepo = $this->createMock(LocationRepositoryInterface::class);
        $idGen = $this->createMock(IdGeneratorInterface::class);

        $entryRepo->method('findById')->willReturn($entry);
        $productRepo->method('findById')->willReturn($product);

        $handler = new AddEntryItemHandler($entryRepo, $entryItemRepo, $productRepo, $locRepo, $idGen);

        $this->expectException(LocationNotFoundException::class);

        ($handler)(new AddEntryItemCommand(
            entryId:   self::ENTRY_ID,
            productId: self::PRODUCT_ID,
            locationId: self::LOCATION_ID,
            serialNumber: null,
            quantity:  1,
            purchaseOrderItemId: null,
        ));
    }
}
