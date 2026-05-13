<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelEntry;

use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;

final readonly class CancelEntryHandler
{
    public function __construct(
        private EntryRepositoryInterface $entryRepository,
    ) {
    }

    public function __invoke(CancelEntryCommand $command): void
    {
        $id    = new EntryIdValueObject($command->id);
        $entry = $this->entryRepository->findById($id)
            ?? throw EntryNotFoundException::withId($command->id);

        $entry->cancel();

        $this->entryRepository->save($entry);
    }
}
