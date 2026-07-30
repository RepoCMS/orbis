<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Application\Query\FindEntryById\FindEntryByIdHandler;
use App\Inventory\Application\Query\Response\EntryItemResponse;
use App\Inventory\Application\Query\Response\EntryResponse;
use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Entities\EntryItem;
use App\Inventory\Domain\Enums\EntryStatus;
use App\Inventory\Domain\Repositories\EntryItemRepositoryInterface;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Inventory\Domain\ValueObjects\EntryItemIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use PHPUnit\Framework\TestCase;

final class FindEntryByIdHandlerTest extends TestCase
{
    private const ENTRY_ID    = '01HZ0000000000000000000010';
    private const CREATED_BY  = '01HZ0000000000000000000040';
    private const ITEM_ID     = '01HZ0000000000000000000020';
    private const PRODUCT_ID  = '01HZ0000000000000000000001';
    private const LOCATION_ID = '01HZ0000000000000000000002';
    private const SERIAL_NO   = 'SN-001';

    private function makeEntry(EntryStatus $status = EntryStatus::DRAFT): Entry
    {
        $entry = Entry::create(
            new EntryIdValueObject(self::ENTRY_ID),
            'ENT-001',
            'Entrada de prueba',
            new UserIdValueObject(self::CREATED_BY),
        );

        if ($status === EntryStatus::CONFIRMED) {
            $entry->confirm();
        }

        return $entry;
    }

    /** @return EntryItem[] */
    private function makeItems(): array
    {
        return [
            EntryItem::createSerialized(
                new EntryItemIdValueObject(self::ITEM_ID),
                new EntryIdValueObject(self::ENTRY_ID),
                new ProductIdValueObject(self::PRODUCT_ID),
                new LocationIdValueObject(self::LOCATION_ID),
                new StockSerialNumberValueObject(self::SERIAL_NO),
            ),
        ];
    }

    private function makeHandler(?Entry $entry = null, array $items = []): FindEntryByIdHandler
    {
        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);

        $itemRepo = $this->createMock(EntryItemRepositoryInterface::class);
        $itemRepo->method('findByEntry')->willReturn($items);

        return new FindEntryByIdHandler($entryRepo, $itemRepo);
    }

    public function testReturnsEntryResponseWithItemsWhenFound(): void
    {
        $handler = $this->makeHandler($this->makeEntry(), $this->makeItems());

        $response = ($handler)(self::ENTRY_ID);

        self::assertArrayHasKey('entry', $response);
        self::assertArrayHasKey('items', $response);
        self::assertInstanceOf(EntryResponse::class, $response['entry']);
        self::assertInstanceOf(EntryItemResponse::class, $response['items'][0]);
        self::assertSame(self::ENTRY_ID, $response['entry']->id);
        self::assertSame('ENT-001', $response['entry']->reference);
        self::assertSame(EntryStatus::DRAFT->value, $response['entry']->status);
        self::assertSame('Borrador', $response['entry']->statusLabel);
        self::assertSame('Entrada de prueba', $response['entry']->notes);
        self::assertSame(self::CREATED_BY, $response['entry']->createdBy);
        self::assertNull($response['entry']->confirmedAt);
        self::assertCount(1, $response['items']);
        self::assertSame(self::ITEM_ID, $response['items'][0]->id);
        self::assertSame(self::PRODUCT_ID, $response['items'][0]->productId);
        self::assertSame(self::SERIAL_NO, $response['items'][0]->serialNumber);
        self::assertTrue($response['items'][0]->serialized);
    }

    public function testReturnsConfirmedEntryWithDate(): void
    {
        $handler = $this->makeHandler($this->makeEntry(EntryStatus::CONFIRMED), $this->makeItems());

        $response = ($handler)(self::ENTRY_ID);

        self::assertSame(EntryStatus::CONFIRMED->value, $response['entry']->status);
        self::assertSame('Confirmada', $response['entry']->statusLabel);
        self::assertNotNull($response['entry']->confirmedAt);
    }

    public function testReturnsNonserializedItems(): void
    {
        $item = EntryItem::createNonserialized(
            new EntryItemIdValueObject('01HZ0000000000000000000021'),
            new EntryIdValueObject(self::ENTRY_ID),
            new ProductIdValueObject(self::PRODUCT_ID),
            new LocationIdValueObject(self::LOCATION_ID),
            10,
        );

        $handler = $this->makeHandler($this->makeEntry(), [$item]);

        $response = ($handler)(self::ENTRY_ID);

        self::assertCount(1, $response['items']);
        self::assertSame(10, $response['items'][0]->quantity);
        self::assertFalse($response['items'][0]->serialized);
        self::assertNull($response['items'][0]->serialNumber);
    }

    public function testThrowsExceptionWhenNotFound(): void
    {
        $handler = $this->makeHandler(null);

        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Entrada con ID "%s" no encontrada.', self::ENTRY_ID));

        ($handler)(self::ENTRY_ID);
    }
}
