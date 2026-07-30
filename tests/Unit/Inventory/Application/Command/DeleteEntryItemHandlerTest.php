<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\DeleteEntryItem\DeleteEntryItemCommand;
use App\Inventory\Application\Command\DeleteEntryItem\DeleteEntryItemHandler;
use App\Inventory\Application\Exception\EntryItemNotFoundException;
use App\Inventory\Domain\Entities\EntryItem;
use App\Inventory\Domain\Repositories\EntryItemRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryItemIdValueObject;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use PHPUnit\Framework\TestCase;

final class DeleteEntryItemHandlerTest extends TestCase
{
    private const ITEM_ID = '01HZ0000000000000000000001';

    public function testDeletesEntryItem(): void
    {
        $item = EntryItem::createNonserialized(
            id: EntryItemIdValueObject::fromString(self::ITEM_ID),
            entryId: EntryIdValueObject::fromString('01HZ0000000000000000000002'),
            productId: ProductIdValueObject::fromString('01HZ0000000000000000000003'),
            locationId: LocationIdValueObject::fromString('01HZ0000000000000000000004'),
            quantity: 5,
        );

        $repo = $this->createMock(EntryItemRepositoryInterface::class);
        $repo->method('findById')->willReturn($item);
        $repo->expects(self::once())->method('delete')->with($item);

        $handler = new DeleteEntryItemHandler($repo);
        ($handler)(new DeleteEntryItemCommand(entryItemId: self::ITEM_ID));
    }

    public function testThrowsWhenItemNotFound(): void
    {
        $repo = $this->createMock(EntryItemRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new DeleteEntryItemHandler($repo);

        $this->expectException(EntryItemNotFoundException::class);
        ($handler)(new DeleteEntryItemCommand(entryItemId: '01HZ0000000000000000000099'));
    }
}
