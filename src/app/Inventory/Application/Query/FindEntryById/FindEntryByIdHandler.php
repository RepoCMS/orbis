<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\FindEntryById;

use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Application\Query\Response\EntryItemResponse;
use App\Inventory\Application\Query\Response\EntryResponse;
use App\Inventory\Domain\Repositories\EntryItemRepositoryInterface;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;

final readonly class FindEntryByIdHandler
{
    public function __construct(
        private EntryRepositoryInterface $entryRepository,
        private EntryItemRepositoryInterface $entryItemRepository,
    ) {
    }

    /** @return array{entry: EntryResponse, items: EntryItemResponse[]} */
    public function __invoke(string $id): array
    {
        $entryId = new EntryIdValueObject($id);
        $entry   = $this->entryRepository->findById($entryId)
            ?? throw EntryNotFoundException::withId($id);

        $items = array_map(
            fn ($item) => EntryItemResponse::fromEntity($item),
            $this->entryItemRepository->findByEntry($entryId),
        );

        return [
            'entry' => EntryResponse::fromEntity($entry),
            'items' => $items,
        ];
    }
}
