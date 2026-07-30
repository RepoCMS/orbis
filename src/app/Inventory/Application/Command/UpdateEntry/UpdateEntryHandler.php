<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\UpdateEntry;

use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Application\Exception\EntryReferenceAlreadyExistsException;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;

final readonly class UpdateEntryHandler
{
    public function __construct(
        private EntryRepositoryInterface $entryRepository,
    ) {
    }

    public function __invoke(UpdateEntryCommand $command): void
    {
        $id    = new EntryIdValueObject($command->entryId);
        $entry = $this->entryRepository->findById($id)
            ?? throw EntryNotFoundException::withId($command->entryId);

        if ($command->reference !== $entry->reference()) {
            $existing = $this->entryRepository->findByReference($command->reference);
            if ($existing !== null && !$existing->id()->equals($id)) {
                throw EntryReferenceAlreadyExistsException::withReference($command->reference);
            }
        }

        $entry->update($command->reference, $command->notes, $command->totalCost);
        $this->entryRepository->save($entry);
    }
}
