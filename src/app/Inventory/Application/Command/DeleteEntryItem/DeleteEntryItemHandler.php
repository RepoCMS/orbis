<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DeleteEntryItem;

use App\Inventory\Application\Exception\EntryItemNotFoundException;
use App\Inventory\Domain\Repositories\EntryItemRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryItemIdValueObject;

final readonly class DeleteEntryItemHandler
{
    public function __construct(
        private EntryItemRepositoryInterface $entryItemRepository,
    ) {
    }

    public function __invoke(DeleteEntryItemCommand $command): void
    {
        $itemId = new EntryItemIdValueObject($command->entryItemId);
        $item = $this->entryItemRepository->findById($itemId)
            ?? throw EntryItemNotFoundException::withId($command->entryItemId);

        $this->entryItemRepository->delete($item);
    }
}
